<?php

namespace App\Http\Controllers;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Models\User;
use App\Support\BillingPlanSettings;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MatrixController extends Controller
{
    public function index(): Response
    {
        $this->ensureBoss();

        $matrizes = Matriz::query()
            ->withCount(['units', 'users'])
            ->orderBy('nome')
            ->get();

        return Inertia::render('Matrizes/Index', [
            'matrizes' => $matrizes,
        ]);
    }

    public function create(): Response
    {
        $this->ensureBoss();

        return Inertia::render('Matrizes/Create', [
            'planSettings' => BillingPlanSettings::current(),
        ]);
    }

    public function edit(Matriz $matriz): Response
    {
        $this->ensureBoss();
        $matrixUnit = $matriz->units()
            ->where('tb2_tipo', 'matriz')
            ->first([
                'tb2_id',
                'tb2_nome',
                'tb2_endereco',
                'tb2_cep',
                'tb2_fone',
                'tb2_cnpj',
                'tb2_localizacao',
            ]);
        $branchUnits = $matriz->units()
            ->where('tb2_tipo', 'filial')
            ->orderBy('tb2_nome')
            ->get([
                'tb2_id',
                'tb2_nome',
                'tb2_status',
                'plano_mensal_valor',
                'plano_contratado_em',
            ]);

        return Inertia::render('Matrizes/Edit', [
            'matriz' => $matriz,
            'matrixUnit' => $matrixUnit,
            'branchUnits' => $branchUnits,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureBoss();

        $data = $request->validate([
            'matriz_nome' => ['required', 'string', 'max:255'],
            'matriz_cnpj' => ['nullable', 'string', 'max:20'],
            'master_name' => ['required', 'string', 'max:255'],
            'master_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'master_password' => ['required', 'string', 'min:4', 'confirmed'],
            'unit_name' => ['required', 'string', 'max:255'],
            'unit_address' => ['required', 'string', 'max:255'],
            'unit_cep' => ['required', 'string', 'max:20'],
            'unit_phone' => ['required', 'string', 'max:20'],
            'unit_cnpj' => ['required', 'string', 'max:20'],
            'unit_location' => ['required', 'url', 'max:512'],
        ]);

        $createdAccessCode = null;
        $planSettings = BillingPlanSettings::current();

        DB::transaction(function () use ($data, $planSettings, &$createdAccessCode) {
            $matriz = Matriz::create([
                'nome' => $data['matriz_nome'],
                'slug' => $this->generateUniqueSlug($data['matriz_nome']),
                'cnpj' => $data['matriz_cnpj'] ?: null,
                'status' => 1,
                'plano_mensal_valor' => $planSettings['matrix_monthly_price'],
                'plano_contratado_em' => now(),
            ]);

            $unit = Unidade::create([
                'matriz_id' => $matriz->id,
                'tb2_tipo' => 'matriz',
                'tb2_nome' => $data['unit_name'],
                'tb2_endereco' => $data['unit_address'],
                'tb2_cep' => $data['unit_cep'],
                'tb2_fone' => $data['unit_phone'],
                'tb2_cnpj' => $data['unit_cnpj'],
                'tb2_localizacao' => $data['unit_location'],
                'tb2_status' => 1,
                'plano_mensal_valor' => $planSettings['matrix_monthly_price'],
                'plano_contratado_em' => now(),
            ]);

            $accessCode = $this->generateUniqueAccessCode($data['master_password']);

            $user = User::create([
                'name' => $data['master_name'],
                'email' => Str::lower($data['master_email']),
                'password' => $data['master_password'],
                'cod_acesso' => $accessCode,
                'funcao' => 0,
                'funcao_original' => 0,
                'hr_ini' => '00:00',
                'hr_fim' => '23:00',
                'salario' => 0,
                'vr_cred' => 0,
                'tb2_id' => $unit->tb2_id,
                'matriz_id' => $matriz->id,
            ]);

            $user->units()->sync([$unit->tb2_id]);
            $createdAccessCode = $accessCode;
        });

        return redirect()->route('matrizes.index')->with(
            'success',
            "Matriz cadastrada com sucesso. Codigo de acesso do master: {$createdAccessCode}."
        );
    }

    public function update(Request $request, Matriz $matriz): RedirectResponse
    {
        $this->ensureBoss();

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'unit_name' => ['required', 'string', 'max:255'],
            'unit_address' => ['required', 'string', 'max:255'],
            'unit_cep' => ['required', 'string', 'max:20'],
            'unit_phone' => ['required', 'string', 'max:20'],
            'unit_cnpj' => ['required', 'string', 'max:20'],
            'unit_location' => ['required', 'url', 'max:512'],
            'status' => ['required', 'boolean'],
            'pagamento_ativo' => ['required', 'boolean'],
            'plano_mensal_valor' => ['required', 'numeric', 'min:0'],
            'plano_contratado_em' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $matrixUnit = $matriz->units()
            ->where('tb2_tipo', 'matriz')
            ->first();

        if (! $matrixUnit) {
            abort(404, 'Unidade matriz nao encontrada.');
        }

        DB::transaction(function () use ($data, $matriz, $matrixUnit) {
            $contractedAt = $data['plano_contratado_em']
                ? Carbon::createFromFormat('Y-m-d', $data['plano_contratado_em'])->startOfDay()
                : null;

            $monthlyValue = round((float) $data['plano_mensal_valor'], 2);

            $matriz->fill([
                'nome' => $data['nome'],
                'slug' => $this->generateUniqueSlug($data['nome'], $matriz),
                'cnpj' => $data['cnpj'] ?: null,
                'status' => (int) $data['status'],
                'pagamento_ativo' => (bool) $data['pagamento_ativo'],
                'plano_mensal_valor' => $monthlyValue,
                'plano_contratado_em' => $contractedAt,
            ])->save();

            $matrixUnit->fill([
                'tb2_nome' => $data['unit_name'],
                'tb2_endereco' => $data['unit_address'],
                'tb2_cep' => $data['unit_cep'],
                'tb2_fone' => $data['unit_phone'],
                'tb2_cnpj' => $data['unit_cnpj'],
                'tb2_localizacao' => $data['unit_location'],
                'tb2_status' => (int) $data['status'],
                'pagamento_ativo' => (bool) $data['pagamento_ativo'],
                'plano_mensal_valor' => $monthlyValue,
                'plano_contratado_em' => $contractedAt,
            ])->save();
        });

        return redirect()->route('matrizes.index')->with('success', 'Dados da matriz atualizados com sucesso.');
    }

    public function updateBranchMonthlyValue(Request $request, Matriz $matriz, Unidade $unit): RedirectResponse
    {
        $this->ensureBoss();

        if ((int) $unit->matriz_id !== (int) $matriz->id || (string) $unit->tb2_tipo !== 'filial') {
            abort(404, 'Filial nao encontrada para esta matriz.');
        }

        $data = $request->validate([
            'plano_mensal_valor' => ['required', 'numeric', 'min:0'],
        ]);

        $unit->update([
            'plano_mensal_valor' => round((float) $data['plano_mensal_valor'], 2),
        ]);

        return redirect()
            ->route('matrizes.edit', $matriz)
            ->with('success', 'Mensalidade da filial atualizada com sucesso.');
    }

    private function ensureBoss(): void
    {
        if (! ManagementScope::isBoss(request()->user())) {
            abort(403, 'Acesso negado.');
        }
    }

    private function generateUniqueSlug(string $name, ?Matriz $ignore = null): string
    {
        $baseSlug = Str::slug($name) ?: 'matriz';
        $slug = $baseSlug;
        $suffix = 2;

        while (
            Matriz::query()
                ->where('slug', $slug)
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function generateUniqueAccessCode(?string $preferred = null): string
    {
        $candidate = preg_match('/^\d{4}$/', (string) $preferred) ? (string) $preferred : null;

        if ($candidate !== null && ! User::where('cod_acesso', $candidate)->exists()) {
            return $candidate;
        }

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $generated = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! User::where('cod_acesso', $generated)->exists()) {
                return $generated;
            }
        }

        for ($number = 0; $number <= 9999; $number++) {
            $generated = str_pad((string) $number, 4, '0', STR_PAD_LEFT);

            if (! User::where('cod_acesso', $generated)->exists()) {
                return $generated;
            }
        }

        abort(500, 'Nao foi possivel gerar um codigo de acesso unico.');
    }
}

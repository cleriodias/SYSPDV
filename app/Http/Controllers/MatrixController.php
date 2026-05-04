<?php

namespace App\Http\Controllers;

use App\Models\Aplicacao;
use App\Models\Matriz;
use App\Models\Unidade;
use App\Models\User;
use App\Support\BillingPlanSettings;
use App\Support\ManagementScope;
use App\Support\RecurringBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MatrixController extends Controller
{
    public function index(): Response
    {
        $this->ensureBoss();

        $matrizes = Matriz::query()
            ->with('aplicacao:tb28_id,tb28_nome')
            ->withCount(['units', 'users'])
            ->orderBy('nome')
            ->get()
            ->map(fn (Matriz $matriz) => $this->formatMatrixApplication($matriz));

        return Inertia::render('Matrizes/Index', [
            'matrizes' => $matrizes,
            'planSettings' => BillingPlanSettings::current(),
        ]);
    }

    public function create(): Response
    {
        $this->ensureBoss();

        return Inertia::render('Matrizes/Create', [
            'applications' => $this->formattedApplications(),
            'planSettings' => BillingPlanSettings::current(),
        ]);
    }

    public function edit(Matriz $matriz): Response
    {
        $this->ensureBoss();
        $matrixUnit = $this->resolveMatrixUnit($matriz, true);
        $masterUser = $this->resolveMatrixMaster($matriz);
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
            'applications' => $this->formattedApplications(),
            'matriz' => $matriz,
            'matrixUnit' => $matrixUnit,
            'masterUser' => $masterUser ? [
                'id' => (int) $masterUser->id,
                'name' => $masterUser->name,
                'email' => $masterUser->email,
            ] : null,
            'branchUnits' => $branchUnits,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureBoss();

        $data = $request->validate([
            'matriz_nome' => ['required', 'string', 'max:255'],
            'matriz_cnpj' => ['nullable', 'string', 'max:20'],
            'tb28_id' => ['required', 'integer', 'exists:tb28_aplicacoes,tb28_id'],
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
        $createdMatrixId = null;
        $planSettings = BillingPlanSettings::current();

        DB::transaction(function () use ($data, $planSettings, &$createdAccessCode, &$createdMatrixId) {
            $matriz = Matriz::create([
                'nome' => $data['matriz_nome'],
                'slug' => $this->generateUniqueSlug($data['matriz_nome']),
                'cnpj' => $data['matriz_cnpj'] ?: null,
                'tb28_id' => (int) $data['tb28_id'],
                'status' => 1,
                'plano_mensal_valor' => $planSettings['matrix_monthly_price'],
                'plano_contratado_em' => now(),
            ]);
            $createdMatrixId = (int) $matriz->id;

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

        $createdMatrix = $createdMatrixId
            ? Matriz::query()->with('units')->find($createdMatrixId)
            : null;

        if ($createdMatrix instanceof Matriz) {
            app(RecurringBillingService::class)->syncMatrix(
                $createdMatrix,
                now()->startOfDay(),
                (float) $planSettings['matrix_monthly_price']
            );
        }

        return redirect()->route('matrizes.index')->with(
            'success',
            "Matriz cadastrada com sucesso. Codigo de acesso do master: {$createdAccessCode}."
        );
    }

    public function update(Request $request, Matriz $matriz): RedirectResponse
    {
        $this->ensureBoss();
        $matrixUnit = $this->resolveMatrixUnit($matriz);
        $masterUser = $this->resolveMatrixMaster($matriz);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'tb28_id' => ['required', 'integer', 'exists:tb28_aplicacoes,tb28_id'],
            'master_name' => ['required', 'string', 'max:255'],
            'master_email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($masterUser?->id),
            ],
            'master_password' => [$masterUser ? 'nullable' : 'required', 'string', 'min:4', 'confirmed'],
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

        DB::transaction(function () use ($data, $matriz, $matrixUnit, $masterUser) {
            $contractedAt = $data['plano_contratado_em']
                ? Carbon::createFromFormat('Y-m-d', $data['plano_contratado_em'])->startOfDay()
                : null;

            $monthlyValue = round((float) $data['plano_mensal_valor'], 2);

            $matriz->fill([
                'nome' => $data['nome'],
                'slug' => $this->generateUniqueSlug($data['nome'], $matriz),
                'cnpj' => $data['cnpj'] ?: null,
                'tb28_id' => (int) $data['tb28_id'],
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

            if ($masterUser) {
                $masterUser->fill([
                    'name' => $data['master_name'],
                    'email' => Str::lower($data['master_email']),
                    'tb2_id' => $matrixUnit->tb2_id,
                    'matriz_id' => $matriz->id,
                ]);

                if (filled($data['master_password'] ?? null)) {
                    $masterUser->password = $data['master_password'];
                }

                $masterUser->save();
                $masterUser->units()->syncWithoutDetaching([$matrixUnit->tb2_id]);
            } else {
                $accessCode = $this->generateUniqueAccessCode($data['master_password']);

                $createdMaster = User::create([
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
                    'tb2_id' => $matrixUnit->tb2_id,
                    'matriz_id' => $matriz->id,
                ]);

                $createdMaster->units()->sync([$matrixUnit->tb2_id]);
            }
        });

        app(RecurringBillingService::class)->syncMatrix(
            $matriz->fresh(['units']),
            now()->startOfDay(),
            (float) $data['plano_mensal_valor']
        );

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

        app(RecurringBillingService::class)->syncUnit(
            $unit->fresh(),
            now()->startOfDay(),
            round((float) $data['plano_mensal_valor'], 2)
        );

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

    private function resolveMatrixUnit(Matriz $matriz, bool $limitColumns = false): Unidade
    {
        $columns = $limitColumns
            ? [
                'tb2_id',
                'tb2_nome',
                'tb2_endereco',
                'tb2_cep',
                'tb2_fone',
                'tb2_cnpj',
                'tb2_localizacao',
            ]
            : ['*'];

        $matrixUnit = $matriz->units()
            ->where('tb2_tipo', 'matriz')
            ->first($columns);

        if ($matrixUnit) {
            return $matrixUnit;
        }

        $fallbackUnit = $matriz->units()
            ->orderBy('tb2_id')
            ->first();

        if ($fallbackUnit) {
            $fallbackUnit->fill([
                'tb2_tipo' => 'matriz',
                'tb2_status' => (int) ($fallbackUnit->tb2_status ?? $matriz->status ?? 1),
                'pagamento_ativo' => (bool) ($fallbackUnit->pagamento_ativo ?? $matriz->pagamento_ativo ?? true),
                'login_liberado' => (bool) ($fallbackUnit->login_liberado ?? true),
            ])->save();

            return $this->reloadMatrixUnit($fallbackUnit, $columns);
        }

        $createdUnit = Unidade::create([
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'matriz',
            'tb2_nome' => $matriz->nome,
            'tb2_endereco' => 'Endereco nao informado',
            'tb2_cep' => '00000-000',
            'tb2_fone' => '(00) 00000-0000',
            'tb2_cnpj' => preg_replace('/\D+/', '', (string) ($matriz->cnpj ?? '')) ?: '00000000000000',
            'tb2_localizacao' => 'https://maps.google.com/?q=' . rawurlencode($matriz->nome),
            'tb2_status' => (int) ($matriz->status ?? 1),
            'plano_mensal_valor' => $matriz->plano_mensal_valor,
            'plano_contratado_em' => $matriz->plano_contratado_em,
            'pagamento_ativo' => (bool) ($matriz->pagamento_ativo ?? true),
            'login_liberado' => true,
        ]);

        return $this->reloadMatrixUnit($createdUnit, $columns);
    }

    private function reloadMatrixUnit(Unidade $unit, array $columns): Unidade
    {
        return Unidade::query()
            ->whereKey($unit->getKey())
            ->firstOrFail($columns);
    }

    private function resolveMatrixMaster(Matriz $matriz): ?User
    {
        return $matriz->users()
            ->where('funcao_original', 0)
            ->orderBy('id')
            ->first();
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

    private function formattedApplications()
    {
        return Aplicacao::query()
            ->orderBy('tb28_id')
            ->get(['tb28_id', 'tb28_nome'])
            ->map(fn (Aplicacao $application) => [
                'tb28_id' => (int) $application->tb28_id,
                'tb28_nome' => $this->applicationDisplayName($application->tb28_id, $application->tb28_nome),
            ]);
    }

    private function formatMatrixApplication(Matriz $matriz): Matriz
    {
        if ($matriz->relationLoaded('aplicacao') && $matriz->aplicacao) {
            $matriz->aplicacao->tb28_nome = $this->applicationDisplayName(
                (int) $matriz->aplicacao->tb28_id,
                $matriz->aplicacao->tb28_nome
            );
        }

        return $matriz;
    }

    private function applicationDisplayName(int $applicationId, ?string $defaultName): string
    {
        return match ($applicationId) {
            Aplicacao::NFE => 'NFe Corretora de Seguros',
            default => (string) $defaultName,
        };
    }
}

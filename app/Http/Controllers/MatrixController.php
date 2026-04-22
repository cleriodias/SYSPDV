<?php

namespace App\Http\Controllers;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Models\User;
use App\Support\BillingPlanSettings;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            $baseSlug = Str::slug($data['matriz_nome']);
            $slug = $baseSlug;
            $suffix = 2;

            while (Matriz::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $matriz = Matriz::create([
                'nome' => $data['matriz_nome'],
                'slug' => $slug,
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

    private function ensureBoss(): void
    {
        if (! ManagementScope::isBoss(request()->user())) {
            abort(403, 'Acesso negado.');
        }
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

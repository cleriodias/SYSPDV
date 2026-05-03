<?php

namespace App\Http\Controllers;

use App\Models\NfeInsurer;
use App\Models\Unidade;
use App\Models\User;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NfeInsurerController extends Controller
{
    private const STATUS_LABELS = [
        1 => 'Ativa',
        0 => 'Inativa',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureManagement($user);

        $matrixId = $this->fallbackMatrixId($user);
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));

        $query = NfeInsurer::query()
            ->where('matriz_id', $matrixId)
            ->orderBy('tb31_nome_fantasia');

        if (in_array($status, ['0', '1'], true)) {
            $query->where('tb31_status', (int) $status);
        } else {
            $status = 'all';
        }

        if ($search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where(function ($builder) use ($safeSearch) {
                $builder
                    ->where('tb31_nome_fantasia', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb31_razao_social', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb31_cnpj', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb31_codigo_susep', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb31_codigo_externo_integracao', 'like', '%' . $safeSearch . '%');
            });
        }

        $insurers = $query
            ->get()
            ->map(fn (NfeInsurer $insurer) => $this->serializeInsurer($insurer))
            ->values();

        return Inertia::render('Nfe/Insurers/Index', [
            'insurers' => $insurers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'summary' => [
                'total' => $insurers->count(),
                'active' => $insurers->where('status', 1)->count(),
                'inactive' => $insurers->where('status', 0)->count(),
                'integrated' => $insurers->where('uses_integration', true)->count(),
            ],
            'statusOptions' => $this->statusOptions(true),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $this->ensureManagement($user);

        return Inertia::render('Nfe/Insurers/Form', [
            'mode' => 'create',
            'statusOptions' => $this->statusOptions(),
            'insurer' => $this->blankInsurer(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureManagement($user);

        $matrixId = $this->fallbackMatrixId($user);
        $validated = $this->validateInsurer($request, null, $matrixId);

        $insurer = NfeInsurer::create([
            'matriz_id' => $matrixId,
            'tb31_nome_fantasia' => trim((string) $validated['tb31_nome_fantasia']),
            'tb31_razao_social' => $this->nullableString($validated['tb31_razao_social'] ?? null),
            'tb31_cnpj' => $this->nullableDigitsString($validated['tb31_cnpj'] ?? null),
            'tb31_codigo_susep' => $this->nullableString($validated['tb31_codigo_susep'] ?? null),
            'tb31_status' => (int) $validated['tb31_status'],
            'tb31_usa_integracao' => (bool) $validated['tb31_usa_integracao'],
            'tb31_codigo_externo_integracao' => $this->nullableString($validated['tb31_codigo_externo_integracao'] ?? null),
            'tb31_observacoes_operacionais' => $this->nullableString($validated['tb31_observacoes_operacionais'] ?? null),
        ]);

        return redirect()
            ->route('nfe.insurers.edit', $insurer->tb31_id)
            ->with('success', 'Seguradora cadastrada com sucesso.');
    }

    public function edit(Request $request, NfeInsurer $insurer): Response
    {
        $user = $request->user();
        $this->ensureManagement($user);
        $this->ensureInsurerAccess($user, $insurer);

        return Inertia::render('Nfe/Insurers/Form', [
            'mode' => 'edit',
            'statusOptions' => $this->statusOptions(),
            'insurer' => $this->serializeInsurerForm($insurer),
        ]);
    }

    public function update(Request $request, NfeInsurer $insurer): RedirectResponse
    {
        $user = $request->user();
        $this->ensureManagement($user);
        $this->ensureInsurerAccess($user, $insurer);

        $validated = $this->validateInsurer($request, $insurer, (int) $insurer->matriz_id);

        $insurer->update([
            'tb31_nome_fantasia' => trim((string) $validated['tb31_nome_fantasia']),
            'tb31_razao_social' => $this->nullableString($validated['tb31_razao_social'] ?? null),
            'tb31_cnpj' => $this->nullableDigitsString($validated['tb31_cnpj'] ?? null),
            'tb31_codigo_susep' => $this->nullableString($validated['tb31_codigo_susep'] ?? null),
            'tb31_status' => (int) $validated['tb31_status'],
            'tb31_usa_integracao' => (bool) $validated['tb31_usa_integracao'],
            'tb31_codigo_externo_integracao' => $this->nullableString($validated['tb31_codigo_externo_integracao'] ?? null),
            'tb31_observacoes_operacionais' => $this->nullableString($validated['tb31_observacoes_operacionais'] ?? null),
        ]);

        return redirect()
            ->route('nfe.insurers.edit', $insurer->tb31_id)
            ->with('success', 'Seguradora atualizada com sucesso.');
    }

    private function ensureManagement(?User $user): void
    {
        if (! $user || ! ManagementScope::isManagement($user)) {
            abort(403, 'Acesso negado.');
        }
    }

    private function ensureInsurerAccess(User $user, NfeInsurer $insurer): void
    {
        if (ManagementScope::isBoss($user)) {
            return;
        }

        if ((int) $insurer->matriz_id !== (int) ManagementScope::scopedMatrixId($user)) {
            abort(403, 'Acesso negado.');
        }
    }

    private function validateInsurer(Request $request, ?NfeInsurer $insurer, int $matrixId): array
    {
        $request->merge([
            'tb31_usa_integracao' => $request->boolean('tb31_usa_integracao', false),
            'tb31_cnpj' => $this->nullableDigitsString($request->input('tb31_cnpj')),
        ]);

        $validated = $request->validate([
            'tb31_nome_fantasia' => [
                'required',
                'string',
                'max:160',
                Rule::unique('tb31_nfe_seguradoras', 'tb31_nome_fantasia')
                    ->where(fn ($query) => $query->where('matriz_id', $matrixId))
                    ->ignore($insurer?->tb31_id, 'tb31_id'),
            ],
            'tb31_razao_social' => ['nullable', 'string', 'max:160'],
            'tb31_cnpj' => ['nullable', 'string', 'max:20'],
            'tb31_codigo_susep' => ['nullable', 'string', 'max:60'],
            'tb31_status' => ['required', Rule::in(['0', '1', 0, 1])],
            'tb31_usa_integracao' => ['required', 'boolean'],
            'tb31_codigo_externo_integracao' => ['nullable', 'string', 'max:100'],
            'tb31_observacoes_operacionais' => ['nullable', 'string', 'max:5000'],
        ], [
            'tb31_nome_fantasia.unique' => 'Ja existe uma seguradora com este nome fantasia nesta matriz.',
        ]);

        if ((bool) ($validated['tb31_usa_integracao'] ?? false)) {
            $integrationErrors = [];

            foreach ([
                'tb31_razao_social' => 'Informe a razao social da seguradora para usar integracao.',
                'tb31_cnpj' => 'Informe o CNPJ da seguradora para usar integracao.',
                'tb31_codigo_susep' => 'Informe o codigo SUSEP ou interno da seguradora para usar integracao.',
                'tb31_codigo_externo_integracao' => 'Informe o codigo externo da integracao da seguradora.',
            ] as $field => $message) {
                if (trim((string) ($validated[$field] ?? '')) === '') {
                    $integrationErrors[$field] = $message;
                }
            }

            if ($integrationErrors !== []) {
                throw ValidationException::withMessages($integrationErrors);
            }
        }

        return $validated;
    }

    private function serializeInsurer(NfeInsurer $insurer): array
    {
        return [
            'id' => (int) $insurer->tb31_id,
            'fantasy_name' => (string) $insurer->tb31_nome_fantasia,
            'company_name' => (string) ($insurer->tb31_razao_social ?? ''),
            'cnpj' => (string) ($insurer->tb31_cnpj ?? ''),
            'susep_code' => (string) ($insurer->tb31_codigo_susep ?? ''),
            'status' => (int) $insurer->tb31_status,
            'status_label' => self::STATUS_LABELS[(int) $insurer->tb31_status] ?? 'Indefinido',
            'uses_integration' => (bool) $insurer->tb31_usa_integracao,
            'external_integration_code' => (string) ($insurer->tb31_codigo_externo_integracao ?? ''),
            'notes' => (string) ($insurer->tb31_observacoes_operacionais ?? ''),
            'edit_url' => route('nfe.insurers.edit', $insurer->tb31_id),
        ];
    }

    private function serializeInsurerForm(NfeInsurer $insurer): array
    {
        return [
            'id' => (int) $insurer->tb31_id,
            'nome_fantasia' => (string) $insurer->tb31_nome_fantasia,
            'razao_social' => (string) ($insurer->tb31_razao_social ?? ''),
            'cnpj' => (string) ($insurer->tb31_cnpj ?? ''),
            'codigo_susep' => (string) ($insurer->tb31_codigo_susep ?? ''),
            'status' => (string) ($insurer->tb31_status ?? 1),
            'usa_integracao' => (bool) $insurer->tb31_usa_integracao,
            'codigo_externo_integracao' => (string) ($insurer->tb31_codigo_externo_integracao ?? ''),
            'observacoes_operacionais' => (string) ($insurer->tb31_observacoes_operacionais ?? ''),
        ];
    }

    private function blankInsurer(): array
    {
        return [
            'id' => null,
            'nome_fantasia' => '',
            'razao_social' => '',
            'cnpj' => '',
            'codigo_susep' => '',
            'status' => '1',
            'usa_integracao' => false,
            'codigo_externo_integracao' => '',
            'observacoes_operacionais' => '',
        ];
    }

    private function fallbackMatrixId(User $user): int
    {
        $scopedMatrixId = (int) ManagementScope::scopedMatrixId($user);

        if ($scopedMatrixId > 0) {
            return $scopedMatrixId;
        }

        $activeUnit = request()?->session()?->get('active_unit');
        $activeUnitId = 0;

        if (is_array($activeUnit)) {
            $activeUnitId = (int) ($activeUnit['id'] ?? $activeUnit['tb2_id'] ?? 0);
        } elseif (is_object($activeUnit)) {
            $activeUnitId = (int) ($activeUnit->id ?? $activeUnit->tb2_id ?? 0);
        }

        if ($activeUnitId > 0 && ManagementScope::canManageUnit($user, $activeUnitId)) {
            return (int) (Unidade::query()
                ->where('tb2_id', $activeUnitId)
                ->value('matriz_id') ?? 0);
        }

        $managedUnit = ManagementScope::managedUnits($user, ['tb2_id', 'matriz_id'])->first();

        return (int) ($managedUnit?->matriz_id ?? 0);
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function nullableDigitsString(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => '1', 'label' => 'Ativa'],
            ['value' => '0', 'label' => 'Inativa'],
        ];

        if (! $includeAll) {
            return $options;
        }

        return array_merge([[
            'value' => 'all',
            'label' => 'Todos os status',
        ]], $options);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NfeInsuranceProduct;
use App\Models\Unidade;
use App\Models\User;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NfeInsuranceProductController extends Controller
{
    private const STATUS_LABELS = [
        1 => 'Ativo',
        0 => 'Inativo',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureManagement($user);

        $units = $this->managedUnits($user);
        $selectedUnitId = $this->resolveSelectedUnitId($request, $units, true);
        $status = trim((string) $request->query('status', 'all'));
        $search = trim((string) $request->query('search', ''));
        $matrixId = $this->resolveMatrixId($user, $selectedUnitId, $units);

        $query = NfeInsuranceProduct::query()
            ->with('unidade:tb2_id,tb2_nome')
            ->where('matriz_id', $matrixId)
            ->orderBy('tb30_nome');

        if ($selectedUnitId > 0) {
            $query->where(function ($builder) use ($selectedUnitId) {
                $builder->whereNull('tb2_id')
                    ->orWhere('tb2_id', $selectedUnitId);
            });
        }

        if (in_array($status, ['0', '1'], true)) {
            $query->where('tb30_status', (int) $status);
        } else {
            $status = 'all';
        }

        if ($search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where(function ($builder) use ($safeSearch) {
                $builder
                    ->where('tb30_codigo', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb30_nome', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb30_seguradora', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb30_ramo', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb30_modalidade', 'like', '%' . $safeSearch . '%');
            });
        }

        $products = $query
            ->paginate(12)
            ->through(fn (NfeInsuranceProduct $product) => $this->serializeListItem($product))
            ->withQueryString();

        $summaryQuery = NfeInsuranceProduct::query()->where('matriz_id', $matrixId);
        if ($selectedUnitId > 0) {
            $summaryQuery->where(function ($builder) use ($selectedUnitId) {
                $builder->whereNull('tb2_id')
                    ->orWhere('tb2_id', $selectedUnitId);
            });
        }

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('tb30_status', 1)->count(),
            'inactive' => (clone $summaryQuery)->where('tb30_status', 0)->count(),
            'insurers' => (clone $summaryQuery)->distinct()->count('tb30_seguradora'),
        ];

        return Inertia::render('Nfe/InsuranceProducts/Index', [
            'units' => $units,
            'selectedUnitId' => $selectedUnitId > 0 ? $selectedUnitId : null,
            'products' => $products,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'summary' => $summary,
            'statusOptions' => $this->statusOptions(true),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $this->ensureManagement($user);

        $units = $this->managedUnits($user);
        $selectedUnitId = $this->resolveSelectedUnitId($request, $units, true);

        return Inertia::render('Nfe/InsuranceProducts/Form', [
            'mode' => 'create',
            'units' => $units,
            'selectedUnitId' => $selectedUnitId > 0 ? $selectedUnitId : null,
            'statusOptions' => $this->statusOptions(),
            'product' => [
                'id' => null,
                'unit_id' => $selectedUnitId > 0 ? (string) $selectedUnitId : '',
                'codigo' => '',
                'nome' => '',
                'seguradora' => '',
                'ramo' => '',
                'modalidade' => '',
                'tipo_contratacao' => 'individual',
                'periodicidade' => 'mensal',
                'cfop' => '',
                'ncm' => '',
                'unidade_padrao' => 'UN',
                'premio_base' => '0.00',
                'comissao_percentual' => '0.00',
                'regras' => '',
                'status' => '1',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureManagement($user);

        $validated = $this->validateProduct($request, null, $user);
        $matrixId = $this->resolveProductMatrixId($user, $validated['tb2_id'] ?? null);

        $product = NfeInsuranceProduct::create([
            'matriz_id' => $matrixId,
            'tb2_id' => $validated['tb2_id'] ?: null,
            'tb30_codigo' => trim((string) $validated['tb30_codigo']),
            'tb30_nome' => trim((string) $validated['tb30_nome']),
            'tb30_seguradora' => trim((string) $validated['tb30_seguradora']),
            'tb30_ramo' => trim((string) $validated['tb30_ramo']),
            'tb30_modalidade' => trim((string) ($validated['tb30_modalidade'] ?? '')),
            'tb30_tipo_contratacao' => trim((string) $validated['tb30_tipo_contratacao']),
            'tb30_periodicidade' => trim((string) $validated['tb30_periodicidade']),
            'tb30_cfop' => preg_replace('/\D+/', '', (string) $validated['tb30_cfop']),
            'tb30_ncm' => $validated['tb30_ncm']
                ? preg_replace('/\D+/', '', (string) $validated['tb30_ncm'])
                : null,
            'tb30_unidade_padrao' => strtoupper(trim((string) $validated['tb30_unidade_padrao'])),
            'tb30_premio_base' => (float) $validated['tb30_premio_base'],
            'tb30_comissao_percentual' => (float) $validated['tb30_comissao_percentual'],
            'tb30_regras' => trim((string) ($validated['tb30_regras'] ?? '')),
            'tb30_status' => (int) $validated['tb30_status'],
        ]);

        return redirect()
            ->route('nfe.insurance-products.edit', [
                'insuranceProduct' => $product->tb30_id,
                'unit_id' => $validated['tb2_id'] ?: null,
            ])
            ->with('success', 'Produto de seguro cadastrado com sucesso.');
    }

    public function edit(Request $request, NfeInsuranceProduct $insuranceProduct): Response
    {
        $user = $request->user();
        $this->ensureManagement($user);
        $this->ensureProductAccess($user, $insuranceProduct);

        return Inertia::render('Nfe/InsuranceProducts/Form', [
            'mode' => 'edit',
            'units' => $this->managedUnits($user),
            'selectedUnitId' => $insuranceProduct->tb2_id ? (int) $insuranceProduct->tb2_id : null,
            'statusOptions' => $this->statusOptions(),
            'product' => [
                'id' => (int) $insuranceProduct->tb30_id,
                'unit_id' => $insuranceProduct->tb2_id ? (string) $insuranceProduct->tb2_id : '',
                'codigo' => (string) $insuranceProduct->tb30_codigo,
                'nome' => (string) $insuranceProduct->tb30_nome,
                'seguradora' => (string) $insuranceProduct->tb30_seguradora,
                'ramo' => (string) $insuranceProduct->tb30_ramo,
                'modalidade' => (string) ($insuranceProduct->tb30_modalidade ?? ''),
                'tipo_contratacao' => (string) $insuranceProduct->tb30_tipo_contratacao,
                'periodicidade' => (string) $insuranceProduct->tb30_periodicidade,
                'cfop' => (string) $insuranceProduct->tb30_cfop,
                'ncm' => (string) ($insuranceProduct->tb30_ncm ?? ''),
                'unidade_padrao' => (string) $insuranceProduct->tb30_unidade_padrao,
                'premio_base' => number_format((float) $insuranceProduct->tb30_premio_base, 2, '.', ''),
                'comissao_percentual' => number_format((float) $insuranceProduct->tb30_comissao_percentual, 2, '.', ''),
                'regras' => (string) ($insuranceProduct->tb30_regras ?? ''),
                'status' => (string) ($insuranceProduct->tb30_status ?? 1),
            ],
        ]);
    }

    public function update(Request $request, NfeInsuranceProduct $insuranceProduct): RedirectResponse
    {
        $user = $request->user();
        $this->ensureManagement($user);
        $this->ensureProductAccess($user, $insuranceProduct);

        $validated = $this->validateProduct($request, $insuranceProduct, $user);
        $matrixId = $this->resolveProductMatrixId($user, $validated['tb2_id'] ?? null);

        $insuranceProduct->update([
            'matriz_id' => $matrixId,
            'tb2_id' => $validated['tb2_id'] ?: null,
            'tb30_codigo' => trim((string) $validated['tb30_codigo']),
            'tb30_nome' => trim((string) $validated['tb30_nome']),
            'tb30_seguradora' => trim((string) $validated['tb30_seguradora']),
            'tb30_ramo' => trim((string) $validated['tb30_ramo']),
            'tb30_modalidade' => trim((string) ($validated['tb30_modalidade'] ?? '')),
            'tb30_tipo_contratacao' => trim((string) $validated['tb30_tipo_contratacao']),
            'tb30_periodicidade' => trim((string) $validated['tb30_periodicidade']),
            'tb30_cfop' => preg_replace('/\D+/', '', (string) $validated['tb30_cfop']),
            'tb30_ncm' => $validated['tb30_ncm']
                ? preg_replace('/\D+/', '', (string) $validated['tb30_ncm'])
                : null,
            'tb30_unidade_padrao' => strtoupper(trim((string) $validated['tb30_unidade_padrao'])),
            'tb30_premio_base' => (float) $validated['tb30_premio_base'],
            'tb30_comissao_percentual' => (float) $validated['tb30_comissao_percentual'],
            'tb30_regras' => trim((string) ($validated['tb30_regras'] ?? '')),
            'tb30_status' => (int) $validated['tb30_status'],
        ]);

        return redirect()
            ->route('nfe.insurance-products.edit', [
                'insuranceProduct' => $insuranceProduct->tb30_id,
                'unit_id' => $validated['tb2_id'] ?: null,
            ])
            ->with('success', 'Produto de seguro atualizado com sucesso.');
    }

    private function ensureManagement(?User $user): void
    {
        if (! $user || ! ManagementScope::isManagement($user)) {
            abort(403, 'Acesso negado.');
        }

        if (! Schema::hasTable('tb30_nfe_produtos_seguro')) {
            abort(503, 'A estrutura de produtos de seguro ainda nao esta disponivel neste ambiente.');
        }
    }

    private function managedUnits(User $user): Collection
    {
        return ManagementScope::managedUnits($user, ['tb2_id', 'tb2_nome', 'matriz_id'])
            ->map(fn (Unidade $unit) => [
                'id' => (int) $unit->tb2_id,
                'name' => (string) $unit->tb2_nome,
                'matriz_id' => (int) ($unit->matriz_id ?? 0),
            ])
            ->values();
    }

    private function resolveSelectedUnitId(Request $request, Collection $units, bool $allowEmpty = false): int
    {
        $selectedUnitId = (int) $request->query('unit_id', 0);

        if ($selectedUnitId <= 0 && ! $allowEmpty && $units->isNotEmpty()) {
            $selectedUnitId = (int) ($units->first()['id'] ?? 0);
        }

        if ($selectedUnitId > 0 && ! $units->contains(fn (array $unit) => (int) $unit['id'] === $selectedUnitId)) {
            abort(403, 'Acesso negado.');
        }

        return $selectedUnitId;
    }

    private function resolveMatrixId(User $user, int $selectedUnitId, Collection $units): int
    {
        if ($selectedUnitId > 0) {
            $unit = $units->first(fn (array $item) => (int) $item['id'] === $selectedUnitId);

            if ($unit) {
                return (int) ($unit['matriz_id'] ?? 0);
            }
        }

        return $this->fallbackMatrixId($user, $units);
    }

    private function ensureProductAccess(User $user, NfeInsuranceProduct $product): void
    {
        if (ManagementScope::isBoss($user)) {
            return;
        }

        $matrixId = (int) ManagementScope::scopedMatrixId($user);

        if ($matrixId <= 0 || $matrixId !== (int) $product->matriz_id) {
            abort(403, 'Acesso negado.');
        }

        if ($product->tb2_id && ! ManagementScope::canManageUnit($user, (int) $product->tb2_id)) {
            abort(403, 'Acesso negado.');
        }
    }

    private function resolveProductMatrixId(User $user, mixed $unitId): int
    {
        $resolvedUnitId = (int) ($unitId ?? 0);

        if ($resolvedUnitId > 0) {
            if (! ManagementScope::canManageUnit($user, $resolvedUnitId)) {
                abort(403, 'Acesso negado.');
            }

            return (int) (Unidade::query()
                ->where('tb2_id', $resolvedUnitId)
                ->value('matriz_id') ?? 0);
        }

        return $this->fallbackMatrixId($user);
    }

    private function fallbackMatrixId(User $user, ?Collection $units = null): int
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
            $activeUnitMatrixId = (int) (Unidade::query()
                ->where('tb2_id', $activeUnitId)
                ->value('matriz_id') ?? 0);

            if ($activeUnitMatrixId > 0) {
                return $activeUnitMatrixId;
            }
        }

        $managedUnits = $units ?? $this->managedUnits($user);

        return (int) ($managedUnits->first()['matriz_id'] ?? 0);
    }

    private function validateProduct(Request $request, ?NfeInsuranceProduct $product, User $user): array
    {
        $matrixId = $this->resolveProductMatrixId($user, $request->input('tb2_id'));

        return $request->validate([
            'tb2_id' => ['nullable', 'integer', 'exists:tb2_unidades,tb2_id'],
            'tb30_codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('tb30_nfe_produtos_seguro', 'tb30_codigo')
                    ->where(fn ($query) => $query->where('matriz_id', $matrixId))
                    ->ignore($product?->tb30_id, 'tb30_id'),
            ],
            'tb30_nome' => ['required', 'string', 'max:255'],
            'tb30_seguradora' => ['required', 'string', 'max:160'],
            'tb30_ramo' => ['required', 'string', 'max:120'],
            'tb30_modalidade' => ['nullable', 'string', 'max:120'],
            'tb30_tipo_contratacao' => ['required', 'string', 'max:80'],
            'tb30_periodicidade' => ['required', 'string', 'max:40'],
            'tb30_cfop' => ['required', 'string', 'size:4'],
            'tb30_ncm' => ['nullable', 'string', 'max:8'],
            'tb30_unidade_padrao' => ['required', 'string', 'max:10'],
            'tb30_premio_base' => ['required', 'numeric', 'gte:0'],
            'tb30_comissao_percentual' => ['required', 'numeric', 'gte:0', 'lte:100'],
            'tb30_regras' => ['nullable', 'string', 'max:5000'],
            'tb30_status' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);
    }

    private function serializeListItem(NfeInsuranceProduct $product): array
    {
        return [
            'id' => (int) $product->tb30_id,
            'code' => (string) $product->tb30_codigo,
            'name' => (string) $product->tb30_nome,
            'insurer' => (string) $product->tb30_seguradora,
            'branch' => (string) $product->tb30_ramo,
            'modality' => (string) ($product->tb30_modalidade ?? ''),
            'unit_name' => $product->tb2_id ? (string) ($product->unidade?->tb2_nome ?? '--') : 'Matriz',
            'contract_type' => (string) $product->tb30_tipo_contratacao,
            'periodicity' => (string) $product->tb30_periodicidade,
            'premium' => (float) $product->tb30_premio_base,
            'commission' => (float) $product->tb30_comissao_percentual,
            'status' => (int) $product->tb30_status,
            'status_label' => self::STATUS_LABELS[(int) $product->tb30_status] ?? 'Indefinido',
            'edit_url' => route('nfe.insurance-products.edit', ['insuranceProduct' => $product->tb30_id, 'unit_id' => $product->tb2_id]),
        ];
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => '1', 'label' => 'Ativo'],
            ['value' => '0', 'label' => 'Inativo'],
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

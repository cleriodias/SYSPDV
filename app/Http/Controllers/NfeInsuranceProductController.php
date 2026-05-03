<?php

namespace App\Http\Controllers;

use App\Models\NfeInsurer;
use App\Models\NfeInsuranceProduct;
use App\Models\Unidade;
use App\Models\User;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NfeInsuranceProductController extends Controller
{
    private const STATUS_LABELS = [
        1 => 'Ativo',
        0 => 'Inativo',
    ];

    private const DEFAULT_NATUREZA_RECEITA = 'premio de seguro';

    private const DEFAULT_RAMO_FISCAL = 'seguro de danos';

    private const DEFAULT_REGRA_BASE_IOF = 'premio';

    private const DEFAULT_IOF_RATE = 7.38;

    private const DEFAULT_ISS_MUNICIPALITY = 'Brasilia';

    private const DEFAULT_ISS_UF = 'DF';

    private const DEFAULT_ISS_IBGE_CODE = '5300108';

    private const DEFAULT_NFSE_ENABLED = true;

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
            ->with(['unidade:tb2_id,tb2_nome', 'insurer:tb31_id,tb31_nome_fantasia'])
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
        $matrixId = $this->resolveMatrixId($user, $selectedUnitId, $units);

        return Inertia::render('Nfe/InsuranceProducts/Form', [
            'mode' => 'create',
            'units' => $units,
            'insurers' => $this->availableInsurers($matrixId),
            'selectedUnitId' => $selectedUnitId > 0 ? $selectedUnitId : null,
            'statusOptions' => $this->statusOptions(),
            'product' => [
                'id' => null,
                'unit_id' => $selectedUnitId > 0 ? (string) $selectedUnitId : '',
                'insurer_id' => '',
                'insurer_name' => '',
                'codigo' => '',
                'nome' => '',
                'ramo' => '',
                'modalidade' => '',
                'tipo_contratacao' => 'individual',
                'periodicidade' => 'mensal',
                'natureza_receita' => self::DEFAULT_NATUREZA_RECEITA,
                'ramo_fiscal' => self::DEFAULT_RAMO_FISCAL,
                'incide_iof' => true,
                'aliquota_iof' => number_format(self::DEFAULT_IOF_RATE, 2, '.', ''),
                'permite_override_iof' => true,
                'regra_base_iof' => self::DEFAULT_REGRA_BASE_IOF,
                'destacar_iof' => true,
                'ha_corretagem' => false,
                'gera_nfse' => self::DEFAULT_NFSE_ENABLED,
                'item_lista_servico' => '10.01',
                'codigo_servico_nfse' => '',
                'municipio_iss' => self::DEFAULT_ISS_MUNICIPALITY,
                'uf_iss' => self::DEFAULT_ISS_UF,
                'codigo_ibge_iss' => self::DEFAULT_ISS_IBGE_CODE,
                'aliquota_iss' => '0.00',
                'prestador_nfse' => '',
                'tomador_nfse' => '',
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
        $insurer = $this->resolveActiveInsurer($matrixId, $validated['tb31_id'] ?? null);
        $payload = $this->buildProductPayload($validated, $matrixId, $insurer);

        $product = NfeInsuranceProduct::create($payload);

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
            'insurers' => $this->availableInsurers((int) $insuranceProduct->matriz_id, (int) $insuranceProduct->tb31_id),
            'selectedUnitId' => $insuranceProduct->tb2_id ? (int) $insuranceProduct->tb2_id : null,
            'statusOptions' => $this->statusOptions(),
            'product' => [
                'id' => (int) $insuranceProduct->tb30_id,
                'unit_id' => $insuranceProduct->tb2_id ? (string) $insuranceProduct->tb2_id : '',
                'insurer_id' => $insuranceProduct->tb31_id ? (string) $insuranceProduct->tb31_id : '',
                'insurer_name' => (string) ($insuranceProduct->insurer?->tb31_nome_fantasia ?? $insuranceProduct->tb30_seguradora),
                'codigo' => (string) $insuranceProduct->tb30_codigo,
                'nome' => (string) $insuranceProduct->tb30_nome,
                'ramo' => (string) $insuranceProduct->tb30_ramo,
                'modalidade' => (string) ($insuranceProduct->tb30_modalidade ?? ''),
                'tipo_contratacao' => (string) $insuranceProduct->tb30_tipo_contratacao,
                'periodicidade' => (string) $insuranceProduct->tb30_periodicidade,
                'natureza_receita' => (string) ($insuranceProduct->tb30_natureza_receita ?? self::DEFAULT_NATUREZA_RECEITA),
                'ramo_fiscal' => (string) ($insuranceProduct->tb30_ramo_fiscal ?? self::DEFAULT_RAMO_FISCAL),
                'incide_iof' => (bool) $insuranceProduct->tb30_incide_iof,
                'aliquota_iof' => number_format((float) $insuranceProduct->tb30_aliquota_iof, 2, '.', ''),
                'permite_override_iof' => (bool) $insuranceProduct->tb30_permite_override_iof,
                'regra_base_iof' => (string) ($insuranceProduct->tb30_regra_base_iof ?? self::DEFAULT_REGRA_BASE_IOF),
                'destacar_iof' => (bool) $insuranceProduct->tb30_destacar_iof,
                'ha_corretagem' => (bool) $insuranceProduct->tb30_ha_corretagem,
                'gera_nfse' => (bool) $insuranceProduct->tb30_gera_nfse,
                'item_lista_servico' => (string) ($insuranceProduct->tb30_item_lista_servico ?? ''),
                'codigo_servico_nfse' => (string) ($insuranceProduct->tb30_codigo_servico_nfse ?? ''),
                'municipio_iss' => $this->defaultMunicipioIssValue($insuranceProduct->tb30_municipio_iss ?? null),
                'uf_iss' => $this->defaultUfIssValue($insuranceProduct->tb30_uf_iss ?? null),
                'codigo_ibge_iss' => $this->defaultCodigoIbgeIssValue($insuranceProduct->tb30_codigo_ibge_iss ?? null),
                'aliquota_iss' => number_format((float) $insuranceProduct->tb30_aliquota_iss, 2, '.', ''),
                'prestador_nfse' => (string) ($insuranceProduct->tb30_prestador_nfse ?? ''),
                'tomador_nfse' => (string) ($insuranceProduct->tb30_tomador_nfse ?? ''),
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
        $insurer = $this->resolveActiveInsurer($matrixId, $validated['tb31_id'] ?? null);
        $payload = $this->buildProductPayload($validated, $matrixId, $insurer);

        $insuranceProduct->update($payload);

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
        $request->merge([
            'tb30_incide_iof' => $request->boolean('tb30_incide_iof', true),
            'tb30_permite_override_iof' => $request->boolean('tb30_permite_override_iof', true),
            'tb30_destacar_iof' => $request->boolean('tb30_destacar_iof', true),
            'tb30_ha_corretagem' => $request->boolean('tb30_ha_corretagem', false),
            'tb30_gera_nfse' => $request->boolean('tb30_gera_nfse', self::DEFAULT_NFSE_ENABLED),
            'tb30_cfop' => $this->nullableString($request->input('tb30_cfop')),
            'tb30_ncm' => $this->nullableString($request->input('tb30_ncm')),
            'tb30_uf_iss' => $this->nullableUpperString($request->input('tb30_uf_iss')),
            'tb30_codigo_ibge_iss' => $this->nullableDigitsString($request->input('tb30_codigo_ibge_iss')),
        ]);

        $matrixId = $this->resolveProductMatrixId($user, $request->input('tb2_id'));

        $validated = $request->validate([
            'tb2_id' => ['nullable', 'integer', 'exists:tb2_unidades,tb2_id'],
            'tb30_codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('tb30_nfe_produtos_seguro', 'tb30_codigo')
                    ->where(fn ($query) => $query->where('matriz_id', $matrixId))
                    ->ignore($product?->tb30_id, 'tb30_id'),
            ],
            'tb31_id' => ['required', 'integer', 'exists:tb31_nfe_seguradoras,tb31_id'],
            'tb30_nome' => ['required', 'string', 'max:255'],
            'tb30_ramo' => ['required', 'string', 'max:120'],
            'tb30_modalidade' => ['nullable', 'string', 'max:120'],
            'tb30_tipo_contratacao' => ['required', 'string', 'max:80'],
            'tb30_periodicidade' => ['required', 'string', 'max:40'],
            'tb30_natureza_receita' => ['required', 'string', 'max:120'],
            'tb30_ramo_fiscal' => ['required', 'string', 'max:120'],
            'tb30_incide_iof' => ['required', 'boolean'],
            'tb30_aliquota_iof' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'tb30_permite_override_iof' => ['required', 'boolean'],
            'tb30_regra_base_iof' => ['nullable', 'string', 'max:160'],
            'tb30_destacar_iof' => ['required', 'boolean'],
            'tb30_ha_corretagem' => ['required', 'boolean'],
            'tb30_gera_nfse' => ['required', 'boolean'],
            'tb30_item_lista_servico' => ['nullable', 'string', 'max:20'],
            'tb30_codigo_servico_nfse' => ['nullable', 'string', 'max:30'],
            'tb30_municipio_iss' => ['nullable', 'string', 'max:120'],
            'tb30_uf_iss' => ['nullable', 'string', 'size:2'],
            'tb30_codigo_ibge_iss' => ['nullable', 'string', 'size:7'],
            'tb30_aliquota_iss' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'tb30_prestador_nfse' => ['nullable', 'string', 'max:160'],
            'tb30_tomador_nfse' => ['nullable', 'string', 'max:160'],
            'tb30_cfop' => ['nullable', 'string', 'size:4'],
            'tb30_ncm' => ['nullable', 'string', 'max:8'],
            'tb30_unidade_padrao' => ['required', 'string', 'max:10'],
            'tb30_premio_base' => ['required', 'numeric', 'gte:0'],
            'tb30_comissao_percentual' => ['required', 'numeric', 'gte:0', 'lte:100'],
            'tb30_regras' => ['nullable', 'string', 'max:5000'],
            'tb30_status' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);

        if (($validated['tb30_gera_nfse'] ?? false) && ! ($validated['tb30_ha_corretagem'] ?? false)) {
            throw ValidationException::withMessages([
                'tb30_gera_nfse' => 'Nao e permitido gerar NFS-e sem corretagem/intermediacao.',
            ]);
        }

        $insurer = $this->resolveInsurerWithinMatrix($matrixId, $validated['tb31_id'] ?? null);

        if ((int) ($insurer->tb31_status ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'tb31_id' => 'Selecione uma seguradora ativa.',
            ]);
        }

        if (($validated['tb30_incide_iof'] ?? false) && (! isset($validated['tb30_aliquota_iof']) || (float) $validated['tb30_aliquota_iof'] <= 0)) {
            throw ValidationException::withMessages([
                'tb30_aliquota_iof' => 'Informe a aliquota padrao do IOF.',
            ]);
        }

        if (($validated['tb30_incide_iof'] ?? false) && trim((string) ($validated['tb30_regra_base_iof'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'tb30_regra_base_iof' => 'Informe a regra de base do IOF.',
            ]);
        }

        if (($validated['tb30_gera_nfse'] ?? false) && trim((string) ($validated['tb30_item_lista_servico'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'tb30_item_lista_servico' => 'Informe o item da lista de servico da NFS-e.',
            ]);
        }

        if (($validated['tb30_gera_nfse'] ?? false) && trim((string) ($validated['tb30_municipio_iss'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'tb30_municipio_iss' => 'Informe o municipio de incidencia do ISS.',
            ]);
        }

        if (($validated['tb30_gera_nfse'] ?? false) && trim((string) ($validated['tb30_uf_iss'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'tb30_uf_iss' => 'Informe a UF do ISS.',
            ]);
        }

        if (($validated['tb30_gera_nfse'] ?? false) && trim((string) ($validated['tb30_codigo_ibge_iss'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'tb30_codigo_ibge_iss' => 'Informe o codigo IBGE do municipio do ISS.',
            ]);
        }

        if (($validated['tb30_gera_nfse'] ?? false) && (! isset($validated['tb30_aliquota_iss']) || (float) $validated['tb30_aliquota_iss'] <= 0)) {
            throw ValidationException::withMessages([
                'tb30_aliquota_iss' => 'Informe a aliquota de ISS.',
            ]);
        }

        if (($validated['tb30_gera_nfse'] ?? false) && trim((string) ($validated['tb30_prestador_nfse'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'tb30_prestador_nfse' => 'Informe o prestador da NFS-e.',
            ]);
        }

        return $this->normalizeValidatedProductData($validated);
    }

    private function serializeListItem(NfeInsuranceProduct $product): array
    {
        return [
            'id' => (int) $product->tb30_id,
            'code' => (string) $product->tb30_codigo,
            'name' => (string) $product->tb30_nome,
            'insurer' => (string) ($product->insurer?->tb31_nome_fantasia ?? $product->tb30_seguradora),
            'branch' => (string) $product->tb30_ramo,
            'modality' => (string) ($product->tb30_modalidade ?? ''),
            'unit_name' => $product->tb2_id ? (string) ($product->unidade?->tb2_nome ?? '--') : 'Matriz',
            'contract_type' => (string) $product->tb30_tipo_contratacao,
            'periodicity' => (string) $product->tb30_periodicidade,
            'natureza_receita' => (string) ($product->tb30_natureza_receita ?? self::DEFAULT_NATUREZA_RECEITA),
            'incide_iof' => (bool) $product->tb30_incide_iof,
            'iof_rate' => (float) ($product->tb30_aliquota_iof ?? 0),
            'has_brokerage' => (bool) $product->tb30_ha_corretagem,
            'nfse_enabled' => (bool) $product->tb30_gera_nfse,
            'premium' => (float) $product->tb30_premio_base,
            'commission' => (float) $product->tb30_comissao_percentual,
            'status' => (int) $product->tb30_status,
            'status_label' => self::STATUS_LABELS[(int) $product->tb30_status] ?? 'Indefinido',
            'edit_url' => route('nfe.insurance-products.edit', ['insuranceProduct' => $product->tb30_id, 'unit_id' => $product->tb2_id]),
        ];
    }

    private function buildProductPayload(array $validated, int $matrixId, NfeInsurer $insurer): array
    {
        return [
            'matriz_id' => $matrixId,
            'tb2_id' => $validated['tb2_id'] ?: null,
            'tb31_id' => (int) $insurer->tb31_id,
            'tb30_codigo' => trim((string) $validated['tb30_codigo']),
            'tb30_nome' => trim((string) $validated['tb30_nome']),
            'tb30_seguradora' => trim((string) $insurer->tb31_nome_fantasia),
            'tb30_ramo' => trim((string) $validated['tb30_ramo']),
            'tb30_modalidade' => trim((string) ($validated['tb30_modalidade'] ?? '')),
            'tb30_tipo_contratacao' => trim((string) $validated['tb30_tipo_contratacao']),
            'tb30_periodicidade' => trim((string) $validated['tb30_periodicidade']),
            'tb30_natureza_receita' => trim((string) $validated['tb30_natureza_receita']),
            'tb30_ramo_fiscal' => trim((string) $validated['tb30_ramo_fiscal']),
            'tb30_incide_iof' => (bool) $validated['tb30_incide_iof'],
            'tb30_aliquota_iof' => (float) ($validated['tb30_aliquota_iof'] ?? 0),
            'tb30_permite_override_iof' => (bool) $validated['tb30_permite_override_iof'],
            'tb30_regra_base_iof' => trim((string) ($validated['tb30_regra_base_iof'] ?? '')),
            'tb30_destacar_iof' => (bool) $validated['tb30_destacar_iof'],
            'tb30_ha_corretagem' => (bool) $validated['tb30_ha_corretagem'],
            'tb30_gera_nfse' => (bool) $validated['tb30_gera_nfse'],
            'tb30_item_lista_servico' => $this->nullableString($validated['tb30_item_lista_servico'] ?? null),
            'tb30_codigo_servico_nfse' => $this->nullableString($validated['tb30_codigo_servico_nfse'] ?? null),
            'tb30_municipio_iss' => $this->nullableString($validated['tb30_municipio_iss'] ?? null),
            'tb30_uf_iss' => $this->nullableUpperString($validated['tb30_uf_iss'] ?? null),
            'tb30_codigo_ibge_iss' => $this->nullableDigitsString($validated['tb30_codigo_ibge_iss'] ?? null),
            'tb30_aliquota_iss' => (float) ($validated['tb30_aliquota_iss'] ?? 0),
            'tb30_prestador_nfse' => $this->nullableString($validated['tb30_prestador_nfse'] ?? null),
            'tb30_tomador_nfse' => $this->nullableString($validated['tb30_tomador_nfse'] ?? null),
            'tb30_cfop' => preg_replace('/\D+/', '', (string) ($validated['tb30_cfop'] ?? '')),
            'tb30_ncm' => $validated['tb30_ncm']
                ? preg_replace('/\D+/', '', (string) $validated['tb30_ncm'])
                : null,
            'tb30_unidade_padrao' => strtoupper(trim((string) $validated['tb30_unidade_padrao'])),
            'tb30_premio_base' => (float) $validated['tb30_premio_base'],
            'tb30_comissao_percentual' => (float) $validated['tb30_comissao_percentual'],
            'tb30_regras' => trim((string) ($validated['tb30_regras'] ?? '')),
            'tb30_status' => (int) $validated['tb30_status'],
        ];
    }

    private function normalizeValidatedProductData(array $validated): array
    {
        if (! ($validated['tb30_incide_iof'] ?? false)) {
            $validated['tb30_aliquota_iof'] = 0;
            $validated['tb30_regra_base_iof'] = null;
            $validated['tb30_destacar_iof'] = false;
        }

        if (! ($validated['tb30_ha_corretagem'] ?? false)) {
            $validated['tb30_gera_nfse'] = false;
        }

        if (! ($validated['tb30_gera_nfse'] ?? false)) {
            $validated['tb30_item_lista_servico'] = null;
            $validated['tb30_codigo_servico_nfse'] = null;
            $validated['tb30_municipio_iss'] = null;
            $validated['tb30_uf_iss'] = null;
            $validated['tb30_codigo_ibge_iss'] = null;
            $validated['tb30_aliquota_iss'] = 0;
            $validated['tb30_prestador_nfse'] = null;
            $validated['tb30_tomador_nfse'] = null;
        }

        return $validated;
    }

    private function availableInsurers(int $matrixId, ?int $selectedInsurerId = null): Collection
    {
        if ($matrixId <= 0) {
            return collect();
        }

        return NfeInsurer::query()
            ->where('matriz_id', $matrixId)
            ->when($selectedInsurerId > 0, function ($query) use ($selectedInsurerId) {
                $query->where(function ($builder) use ($selectedInsurerId) {
                    $builder->where('tb31_status', 1)
                        ->orWhere('tb31_id', $selectedInsurerId);
                });
            }, fn ($query) => $query->where('tb31_status', 1))
            ->orderBy('tb31_nome_fantasia')
            ->get(['tb31_id', 'tb31_nome_fantasia', 'tb31_status', 'tb31_usa_integracao'])
            ->map(fn (NfeInsurer $insurer) => [
                'id' => (int) $insurer->tb31_id,
                'name' => (string) $insurer->tb31_nome_fantasia,
                'status' => (int) $insurer->tb31_status,
                'uses_integration' => (bool) $insurer->tb31_usa_integracao,
            ])
            ->values();
    }

    private function resolveInsurerWithinMatrix(int $matrixId, mixed $insurerId): NfeInsurer
    {
        $resolvedInsurerId = (int) ($insurerId ?? 0);

        $insurer = NfeInsurer::query()
            ->where('tb31_id', $resolvedInsurerId)
            ->where('matriz_id', $matrixId)
            ->first();

        if (! $insurer) {
            throw ValidationException::withMessages([
                'tb31_id' => 'Selecione uma seguradora valida da matriz atual.',
            ]);
        }

        return $insurer;
    }

    private function resolveActiveInsurer(int $matrixId, mixed $insurerId): NfeInsurer
    {
        $insurer = $this->resolveInsurerWithinMatrix($matrixId, $insurerId);

        if ((int) ($insurer->tb31_status ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'tb31_id' => 'Selecione uma seguradora ativa.',
            ]);
        }

        return $insurer;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function nullableUpperString(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function nullableDigitsString(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    private function defaultMunicipioIssValue(?string $value): string
    {
        return $this->nullableString($value) ?? self::DEFAULT_ISS_MUNICIPALITY;
    }

    private function defaultUfIssValue(?string $value): string
    {
        return $this->nullableUpperString($value) ?? self::DEFAULT_ISS_UF;
    }

    private function defaultCodigoIbgeIssValue(?string $value): string
    {
        return $this->nullableDigitsString($value) ?? self::DEFAULT_ISS_IBGE_CODE;
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

<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoFiscal;
use App\Models\NfeInsuranceProduct;
use App\Models\NfeLaunch;
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

class NfeLaunchController extends Controller
{
    private const STATUS_LABELS = [
        'rascunho' => 'Rascunho',
        'revisao' => 'Em revisao',
        'pronto_emissao' => 'Pronto para emissao',
        'cancelada' => 'Cancelada',
        'emitida' => 'Emitida',
    ];

    private const STATUS_COLORS = [
        'rascunho' => 'secondary',
        'revisao' => 'info',
        'pronto_emissao' => 'success',
        'cancelada' => 'dark',
        'emitida' => 'primary',
    ];

    private const EDITABLE_STATUSES = [
        'rascunho',
        'revisao',
        'pronto_emissao',
    ];

    private const WRITABLE_STATUSES = [
        'rascunho',
        'revisao',
        'pronto_emissao',
        'cancelada',
    ];

    private const OPERATION_TYPE_LABELS = [
        'saida' => 'Saida',
        'devolucao' => 'Devolucao',
        'ajuste' => 'Ajuste',
        'complemento' => 'Complemento',
    ];

    private const FINALITY_LABELS = [
        'normal' => 'Normal',
        'complementar' => 'Complementar',
        'ajuste' => 'Ajuste',
        'devolucao' => 'Devolucao',
    ];

    private const PAYMENT_METHOD_LABELS = [
        'dinheiro' => 'Dinheiro',
        'cartao_credito' => 'Cartao de credito',
        'cartao_debito' => 'Cartao de debito',
        'pix' => 'PIX',
        'boleto' => 'Boleto',
        'transferencia' => 'Transferencia',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureManagement($user);

        $units = $this->managedUnits($user);
        $selectedUnitId = $this->resolveSelectedUnitId($request, $units);
        $status = strtolower(trim((string) $request->query('status', 'all')));
        $search = trim((string) $request->query('search', ''));

        $query = NfeLaunch::query()
            ->with('unidade:tb2_id,tb2_nome')
            ->orderByDesc('tb29_data_lancamento')
            ->orderByDesc('tb29_id');

        if ($selectedUnitId > 0) {
            $query->where('tb2_id', $selectedUnitId);
        } else {
            $query->whereIn('tb2_id', $units->pluck('id'));
        }

        if (array_key_exists($status, self::STATUS_LABELS)) {
            $query->where('tb29_status', $status);
        } else {
            $status = 'all';
        }

        if ($search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where(function ($builder) use ($safeSearch) {
                $builder
                    ->where('tb29_numero', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb29_observacoes', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb29_destinatario->nome', 'like', '%' . $safeSearch . '%')
                    ->orWhere('tb29_destinatario->documento', 'like', '%' . $safeSearch . '%');
            });
        }

        $launches = $query
            ->paginate(12)
            ->through(fn (NfeLaunch $launch) => $this->serializeLaunchListItem($launch))
            ->withQueryString();

        $summaryQuery = NfeLaunch::query();

        if ($selectedUnitId > 0) {
            $summaryQuery->where('tb2_id', $selectedUnitId);
        } else {
            $summaryQuery->whereIn('tb2_id', $units->pluck('id'));
        }

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'rascunho' => (clone $summaryQuery)->where('tb29_status', 'rascunho')->count(),
            'revisao' => (clone $summaryQuery)->where('tb29_status', 'revisao')->count(),
            'pronto_emissao' => (clone $summaryQuery)->where('tb29_status', 'pronto_emissao')->count(),
            'cancelada' => (clone $summaryQuery)->where('tb29_status', 'cancelada')->count(),
        ];

        return Inertia::render('Nfe/Launches/Index', [
            'units' => $units,
            'selectedUnitId' => $selectedUnitId > 0 ? $selectedUnitId : null,
            'launches' => $launches,
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
        $selectedUnitId = $this->resolveSelectedUnitId($request, $units);
        $selectedUnit = $selectedUnitId > 0
            ? Unidade::query()->findOrFail($selectedUnitId)
            : null;

        return Inertia::render('Nfe/Launches/Form', [
            'mode' => 'create',
            'units' => $units,
            'selectedUnitId' => $selectedUnitId > 0 ? $selectedUnitId : null,
            'statusOptions' => $this->statusOptions(),
            'operationTypeOptions' => $this->operationTypeOptions(),
            'finalityOptions' => $this->finalityOptions(),
            'paymentMethodOptions' => $this->paymentMethodOptions(),
            'launch' => $this->defaultLaunchPayload($selectedUnitId, $user),
            'insuranceProducts' => $this->insuranceProductOptionsForUnit($selectedUnit),
            'fiscalReady' => $selectedUnit ? $this->unitFiscalReady($selectedUnit) : false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureManagement($user);

        $validated = $this->validateLaunch($request);
        $unit = $this->resolveManagedUnit($user, (int) $validated['tb2_id']);

        $attributes = $this->buildLaunchAttributes($validated, $unit, $user, null);

        $launch = NfeLaunch::create($attributes);
        $launch->update([
            'tb29_numero' => $this->buildLaunchNumber((int) $unit->tb2_id, (int) $launch->tb29_id),
        ]);

        return redirect()
            ->route('nfe.launches.edit', ['launch' => $launch->tb29_id, 'unit_id' => $unit->tb2_id])
            ->with('success', 'Lancamento NFe criado com sucesso.');
    }

    public function edit(Request $request, NfeLaunch $launch): Response
    {
        $user = $request->user();
        $this->ensureManagement($user);
        $this->ensureLaunchAccess($user, $launch);

        $units = $this->managedUnits($user);
        $unit = Unidade::query()->findOrFail((int) $launch->tb2_id);

        return Inertia::render('Nfe/Launches/Form', [
            'mode' => 'edit',
            'units' => $units,
            'selectedUnitId' => (int) $launch->tb2_id,
            'statusOptions' => $this->statusOptions(),
            'operationTypeOptions' => $this->operationTypeOptions(),
            'finalityOptions' => $this->finalityOptions(),
            'paymentMethodOptions' => $this->paymentMethodOptions(),
            'launch' => $this->serializeLaunchForForm($launch),
            'insuranceProducts' => $this->insuranceProductOptionsForUnit($unit),
            'fiscalReady' => $this->unitFiscalReady($unit),
        ]);
    }

    public function update(Request $request, NfeLaunch $launch): RedirectResponse
    {
        $user = $request->user();
        $this->ensureManagement($user);
        $this->ensureLaunchAccess($user, $launch);

        if (! in_array((string) $launch->tb29_status, self::EDITABLE_STATUSES, true)
            && $request->input('tb29_status') !== 'cancelada') {
            throw ValidationException::withMessages([
                'tb29_status' => 'Este lancamento nao permite mais alteracoes estruturais.',
            ]);
        }

        $validated = $this->validateLaunch($request);
        $unit = $this->resolveManagedUnit($user, (int) $validated['tb2_id']);

        $launch->fill($this->buildLaunchAttributes($validated, $unit, $user, $launch));
        $launch->save();

        return redirect()
            ->route('nfe.launches.edit', ['launch' => $launch->tb29_id, 'unit_id' => $unit->tb2_id])
            ->with('success', 'Lancamento NFe atualizado com sucesso.');
    }

    private function ensureManagement(?User $user): void
    {
        if (! $user || ! ManagementScope::isManagement($user)) {
            abort(403, 'Acesso negado.');
        }

        if (! Schema::hasTable('tb29_nfe_lancamentos')) {
            abort(503, 'A estrutura de lancamentos NFe ainda nao esta disponivel neste ambiente.');
        }

        if (! Schema::hasTable('tb30_nfe_produtos_seguro')) {
            abort(503, 'A estrutura de produtos de seguro ainda nao esta disponivel neste ambiente.');
        }
    }

    private function managedUnits(User $user): Collection
    {
        return ManagementScope::managedUnits($user, ['tb2_id', 'tb2_nome', 'tb2_cnpj', 'matriz_id'])
            ->map(fn (Unidade $unit) => [
                'id' => (int) $unit->tb2_id,
                'name' => (string) $unit->tb2_nome,
                'cnpj' => (string) ($unit->tb2_cnpj ?? ''),
                'matriz_id' => (int) ($unit->matriz_id ?? 0),
            ])
            ->values();
    }

    private function resolveSelectedUnitId(Request $request, Collection $units): int
    {
        $selectedUnitId = (int) $request->query('unit_id', 0);

        if ($selectedUnitId <= 0 && $units->isNotEmpty()) {
            $selectedUnitId = (int) ($units->first()['id'] ?? 0);
        }

        if ($selectedUnitId > 0 && ! $units->contains(fn (array $unit) => (int) $unit['id'] === $selectedUnitId)) {
            abort(403, 'Acesso negado.');
        }

        return $selectedUnitId;
    }

    private function resolveManagedUnit(User $user, int $unitId): Unidade
    {
        if (! ManagementScope::canManageUnit($user, $unitId)) {
            abort(403, 'Acesso negado.');
        }

        return Unidade::query()->findOrFail($unitId);
    }

    private function ensureLaunchAccess(User $user, NfeLaunch $launch): void
    {
        if (! ManagementScope::canManageUnit($user, (int) $launch->tb2_id)) {
            abort(403, 'Acesso negado.');
        }
    }

    private function validateLaunch(Request $request): array
    {
        $validated = $request->validate([
            'tb2_id' => ['required', 'integer', 'exists:tb2_unidades,tb2_id'],
            'tb29_status' => ['required', Rule::in(self::WRITABLE_STATUSES)],
            'tb29_tipo_operacao' => ['required', Rule::in(array_keys(self::OPERATION_TYPE_LABELS))],
            'tb29_finalidade' => ['nullable', Rule::in(array_keys(self::FINALITY_LABELS))],
            'tb29_data_lancamento' => ['required', 'date'],
            'tb29_data_emissao' => ['nullable', 'date'],
            'tb29_data_competencia' => ['nullable', 'date'],
            'destinatario.tipo_pessoa' => ['required', Rule::in(['pf', 'pj'])],
            'destinatario.nome' => ['required', 'string', 'max:255'],
            'destinatario.documento' => ['required', 'string', 'max:18'],
            'destinatario.inscricao_estadual' => ['nullable', 'string', 'max:20'],
            'destinatario.email' => ['required', 'email', 'max:255'],
            'destinatario.telefone' => ['required', 'string', 'max:20'],
            'destinatario.cep' => ['required', 'string', 'max:9'],
            'destinatario.logradouro' => ['required', 'string', 'max:255'],
            'destinatario.numero' => ['required', 'string', 'max:30'],
            'destinatario.complemento' => ['nullable', 'string', 'max:255'],
            'destinatario.bairro' => ['required', 'string', 'max:120'],
            'destinatario.cidade' => ['required', 'string', 'max:120'],
            'destinatario.uf' => ['required', 'string', 'size:2'],
            'comercial.natureza_operacao' => ['required', 'string', 'max:120'],
            'comercial.serie' => ['required', 'string', 'max:10'],
            'comercial.indicador_presenca' => ['required', Rule::in(['presencial', 'internet', 'telefone', 'nao_se_aplica'])],
            'comercial.vendedor' => ['nullable', 'string', 'max:120'],
            'comercial.informacoes_adicionais' => ['nullable', 'string', 'max:2000'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_seguro_id' => ['nullable', 'integer', 'exists:tb30_nfe_produtos_seguro,tb30_id'],
            'itens.*.codigo' => ['required', 'string', 'max:30'],
            'itens.*.descricao' => ['required', 'string', 'max:255'],
            'itens.*.seguradora' => ['required', 'string', 'max:160'],
            'itens.*.ramo' => ['required', 'string', 'max:120'],
            'itens.*.modalidade' => ['nullable', 'string', 'max:120'],
            'itens.*.tipo_contratacao' => ['required', 'string', 'max:80'],
            'itens.*.periodicidade' => ['required', 'string', 'max:40'],
            'itens.*.natureza_receita' => ['nullable', 'string', 'max:120'],
            'itens.*.ramo_fiscal' => ['nullable', 'string', 'max:120'],
            'itens.*.incide_iof' => ['nullable', 'boolean'],
            'itens.*.aliquota_iof' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'itens.*.permite_override_iof' => ['nullable', 'boolean'],
            'itens.*.regra_base_iof' => ['nullable', 'string', 'max:160'],
            'itens.*.destacar_iof' => ['nullable', 'boolean'],
            'itens.*.ha_corretagem' => ['nullable', 'boolean'],
            'itens.*.gera_nfse' => ['nullable', 'boolean'],
            'itens.*.item_lista_servico' => ['nullable', 'string', 'max:20'],
            'itens.*.codigo_servico_nfse' => ['nullable', 'string', 'max:30'],
            'itens.*.municipio_iss' => ['nullable', 'string', 'max:120'],
            'itens.*.uf_iss' => ['nullable', 'string', 'size:2'],
            'itens.*.aliquota_iss' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'itens.*.prestador_nfse' => ['nullable', 'string', 'max:160'],
            'itens.*.tomador_nfse' => ['nullable', 'string', 'max:160'],
            'itens.*.ncm' => ['nullable', 'string', 'max:8'],
            'itens.*.cfop' => ['nullable', 'string', 'max:4'],
            'itens.*.unidade' => ['required', 'string', 'max:10'],
            'itens.*.quantidade' => ['required', 'numeric', 'gt:0'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'gte:0'],
            'itens.*.desconto' => ['nullable', 'numeric', 'gte:0'],
            'pagamento.forma_pagamento' => ['required', Rule::in(array_keys(self::PAYMENT_METHOD_LABELS))],
            'pagamento.parcelas' => ['required', 'integer', 'min:1', 'max:36'],
            'pagamento.primeiro_vencimento' => ['required', 'date'],
            'pagamento.condicao_pagamento' => ['nullable', 'string', 'max:120'],
            'pagamento.chave_pagamento' => ['nullable', 'string', 'max:255'],
            'observacoes.interna' => ['nullable', 'string', 'max:4000'],
            'observacoes.fiscal' => ['nullable', 'string', 'max:4000'],
        ]);

        $tipoPessoa = (string) data_get($validated, 'destinatario.tipo_pessoa', '');
        $documento = preg_replace('/\D+/', '', (string) data_get($validated, 'destinatario.documento', ''));
        $cep = preg_replace('/\D+/', '', (string) data_get($validated, 'destinatario.cep', ''));

        if ($tipoPessoa === 'pf' && strlen($documento) !== 11) {
            throw ValidationException::withMessages([
                'destinatario.documento' => 'Para PF informe um CPF valido com 11 digitos.',
            ]);
        }

        if ($tipoPessoa === 'pj' && strlen($documento) !== 14) {
            throw ValidationException::withMessages([
                'destinatario.documento' => 'Para PJ informe um CNPJ valido com 14 digitos.',
            ]);
        }

        if (strlen($cep) !== 8) {
            throw ValidationException::withMessages([
                'destinatario.cep' => 'Informe um CEP valido com 8 digitos.',
            ]);
        }

        return $validated;
    }

    private function buildLaunchAttributes(array $validated, Unidade $unit, User $user, ?NfeLaunch $existing): array
    {
        $insuranceProductMap = $this->insuranceProductMapForUnitAndItems($unit, collect($validated['itens'] ?? []));
        $normalizedItems = collect($validated['itens'])
            ->values()
            ->map(fn (array $item, int $index) => $this->normalizeItem($item, $insuranceProductMap, $index))
            ->all();

        $totals = $this->calculateTotals($normalizedItems);
        $payment = $this->normalizePayment($validated['pagamento'], $totals);
        $recipient = $this->normalizeRecipient($validated['destinatario']);
        $commercial = $this->normalizeCommercial($validated['comercial']);
        $notes = $this->normalizeNotes($validated['observacoes'] ?? []);
        $pendencias = $this->buildPendencias($recipient, $commercial, $normalizedItems, $payment, $unit);

        if (($validated['tb29_status'] ?? '') === 'pronto_emissao'
            && collect($pendencias)->contains(fn (array $item) => ($item['level'] ?? '') === 'critical')) {
            throw ValidationException::withMessages([
                'tb29_status' => 'Resolva as pendencias criticas antes de marcar o lancamento como pronto para emissao.',
            ]);
        }

        $status = (string) $validated['tb29_status'];

        if ($status === 'cancelada' && trim((string) ($notes['interna'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'observacoes.interna' => 'Informe uma observacao interna para cancelar o lancamento.',
            ]);
        }

        return [
            'tb2_id' => (int) $unit->tb2_id,
            'matriz_id' => (int) ($unit->matriz_id ?? 0),
            'user_id' => (int) $user->id,
            'tb29_status' => $status,
            'tb29_tipo_operacao' => (string) $validated['tb29_tipo_operacao'],
            'tb29_finalidade' => filled($validated['tb29_finalidade'] ?? null)
                ? (string) $validated['tb29_finalidade']
                : null,
            'tb29_data_lancamento' => $validated['tb29_data_lancamento'],
            'tb29_data_emissao' => $validated['tb29_data_emissao'] ?: null,
            'tb29_data_competencia' => $validated['tb29_data_competencia'] ?: null,
            'tb29_destinatario' => $recipient,
            'tb29_comercial' => $commercial,
            'tb29_itens' => $normalizedItems,
            'tb29_pagamento' => $payment,
            'tb29_totais' => $totals,
            'tb29_pendencias' => $pendencias,
            'tb29_observacoes' => json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'tb29_historico' => $this->appendHistory($existing, $user, $status),
        ];
    }

    private function insuranceProductMapForUnitAndItems(Unidade $unit, Collection $items): Collection
    {
        $productIds = $items
            ->pluck('produto_seguro_id')
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return NfeInsuranceProduct::query()
            ->where('matriz_id', (int) ($unit->matriz_id ?? 0))
            ->whereIn('tb30_id', $productIds)
            ->where(function ($builder) use ($unit) {
                $builder->whereNull('tb2_id')
                    ->orWhere('tb2_id', (int) $unit->tb2_id);
            })
            ->get([
                'tb30_id',
                'tb30_codigo',
                'tb30_nome',
                'tb30_seguradora',
                'tb30_ramo',
                'tb30_modalidade',
                'tb30_tipo_contratacao',
                'tb30_periodicidade',
                'tb30_natureza_receita',
                'tb30_ramo_fiscal',
                'tb30_incide_iof',
                'tb30_aliquota_iof',
                'tb30_permite_override_iof',
                'tb30_regra_base_iof',
                'tb30_destacar_iof',
                'tb30_ha_corretagem',
                'tb30_gera_nfse',
                'tb30_item_lista_servico',
                'tb30_codigo_servico_nfse',
                'tb30_municipio_iss',
                'tb30_uf_iss',
                'tb30_aliquota_iss',
                'tb30_prestador_nfse',
                'tb30_tomador_nfse',
                'tb30_cfop',
                'tb30_ncm',
                'tb30_unidade_padrao',
                'tb30_premio_base',
            ])
            ->keyBy('tb30_id');
    }

    private function normalizeItem(array $item, Collection $productMap, int $index): array
    {
        $productId = filled($item['produto_seguro_id'] ?? null) ? (int) $item['produto_seguro_id'] : null;
        $product = $productId ? $productMap->get($productId) : null;

        if ($productId && ! $product) {
            throw ValidationException::withMessages([
                "itens.$index.produto_seguro_id" => 'O produto de seguro selecionado nao pertence ao contexto da unidade escolhida.',
            ]);
        }

        $quantity = round((float) $item['quantidade'], 4);
        $unitPrice = round((float) $item['valor_unitario'], 2);
        $discount = round((float) ($item['desconto'] ?? 0), 2);
        $gross = round($quantity * $unitPrice, 2);
        $total = round(max($gross - $discount, 0), 2);

        return [
            'produto_seguro_id' => $productId,
            'codigo' => trim((string) ($item['codigo'] ?? ($product?->tb30_codigo ?? ''))),
            'descricao' => trim((string) ($item['descricao'] ?? ($product?->tb30_nome ?? ''))),
            'seguradora' => trim((string) ($item['seguradora'] ?? ($product?->tb30_seguradora ?? ''))),
            'ramo' => trim((string) ($item['ramo'] ?? ($product?->tb30_ramo ?? ''))),
            'modalidade' => trim((string) ($item['modalidade'] ?? ($product?->tb30_modalidade ?? ''))),
            'tipo_contratacao' => trim((string) ($item['tipo_contratacao'] ?? ($product?->tb30_tipo_contratacao ?? ''))),
            'periodicidade' => trim((string) ($item['periodicidade'] ?? ($product?->tb30_periodicidade ?? ''))),
            'natureza_receita' => trim((string) ($item['natureza_receita'] ?? ($product?->tb30_natureza_receita ?? 'premio de seguro'))),
            'ramo_fiscal' => trim((string) ($item['ramo_fiscal'] ?? ($product?->tb30_ramo_fiscal ?? 'seguro de danos'))),
            'incide_iof' => $this->normalizeBoolean($item['incide_iof'] ?? ($product?->tb30_incide_iof ?? true)),
            'aliquota_iof' => $this->normalizeOptionalDecimal($item['aliquota_iof'] ?? ($product?->tb30_aliquota_iof ?? 0)),
            'permite_override_iof' => $this->normalizeBoolean($item['permite_override_iof'] ?? ($product?->tb30_permite_override_iof ?? true)),
            'regra_base_iof' => trim((string) ($item['regra_base_iof'] ?? ($product?->tb30_regra_base_iof ?? 'premio'))),
            'destacar_iof' => $this->normalizeBoolean($item['destacar_iof'] ?? ($product?->tb30_destacar_iof ?? true)),
            'ha_corretagem' => $this->normalizeBoolean($item['ha_corretagem'] ?? ($product?->tb30_ha_corretagem ?? false)),
            'gera_nfse' => $this->normalizeBoolean($item['gera_nfse'] ?? ($product?->tb30_gera_nfse ?? false)),
            'item_lista_servico' => $this->normalizeOptionalString($item['item_lista_servico'] ?? ($product?->tb30_item_lista_servico ?? null)),
            'codigo_servico_nfse' => $this->normalizeOptionalString($item['codigo_servico_nfse'] ?? ($product?->tb30_codigo_servico_nfse ?? null)),
            'municipio_iss' => $this->normalizeOptionalString($item['municipio_iss'] ?? ($product?->tb30_municipio_iss ?? null)),
            'uf_iss' => $this->normalizeOptionalUpperString($item['uf_iss'] ?? ($product?->tb30_uf_iss ?? null)),
            'aliquota_iss' => $this->normalizeOptionalDecimal($item['aliquota_iss'] ?? ($product?->tb30_aliquota_iss ?? 0)),
            'prestador_nfse' => $this->normalizeOptionalString($item['prestador_nfse'] ?? ($product?->tb30_prestador_nfse ?? null)),
            'tomador_nfse' => $this->normalizeOptionalString($item['tomador_nfse'] ?? ($product?->tb30_tomador_nfse ?? null)),
            'ncm' => preg_replace('/\D+/', '', (string) ($item['ncm'] ?? ($product?->tb30_ncm ?? ''))),
            'cfop' => preg_replace('/\D+/', '', (string) ($item['cfop'] ?? ($product?->tb30_cfop ?? ''))),
            'unidade' => strtoupper(trim((string) ($item['unidade'] ?? ($product?->tb30_unidade_padrao ?? 'UN')))),
            'quantidade' => $quantity,
            'valor_unitario' => $unitPrice,
            'desconto' => $discount,
            'valor_bruto' => $gross,
            'valor_total' => $total,
        ];
    }

    private function calculateTotals(array $items): array
    {
        $subtotal = round(collect($items)->sum('valor_bruto'), 2);
        $discount = round(collect($items)->sum('desconto'), 2);
        $total = round(collect($items)->sum('valor_total'), 2);

        return [
            'subtotal' => $subtotal,
            'desconto' => $discount,
            'total' => $total,
            'quantidade_itens' => count($items),
        ];
    }

    private function normalizeRecipient(array $recipient): array
    {
        return [
            'tipo_pessoa' => (string) $recipient['tipo_pessoa'],
            'nome' => trim((string) $recipient['nome']),
            'documento' => preg_replace('/\D+/', '', (string) $recipient['documento']),
            'inscricao_estadual' => trim((string) ($recipient['inscricao_estadual'] ?? '')),
            'email' => trim((string) $recipient['email']),
            'telefone' => preg_replace('/\D+/', '', (string) $recipient['telefone']),
            'cep' => preg_replace('/\D+/', '', (string) $recipient['cep']),
            'logradouro' => trim((string) $recipient['logradouro']),
            'numero' => trim((string) $recipient['numero']),
            'complemento' => trim((string) ($recipient['complemento'] ?? '')),
            'bairro' => trim((string) $recipient['bairro']),
            'cidade' => trim((string) $recipient['cidade']),
            'uf' => strtoupper(trim((string) $recipient['uf'])),
        ];
    }

    private function normalizeCommercial(array $commercial): array
    {
        return [
            'natureza_operacao' => trim((string) $commercial['natureza_operacao']),
            'serie' => trim((string) $commercial['serie']),
            'indicador_presenca' => trim((string) $commercial['indicador_presenca']),
            'vendedor' => trim((string) ($commercial['vendedor'] ?? '')),
            'informacoes_adicionais' => trim((string) ($commercial['informacoes_adicionais'] ?? '')),
        ];
    }

    private function normalizePayment(array $payment, array $totals): array
    {
        $installments = max((int) $payment['parcelas'], 1);
        $installmentValue = round($totals['total'] / $installments, 2);

        return [
            'forma_pagamento' => trim((string) $payment['forma_pagamento']),
            'parcelas' => $installments,
            'primeiro_vencimento' => $payment['primeiro_vencimento'],
            'condicao_pagamento' => trim((string) ($payment['condicao_pagamento'] ?? '')),
            'chave_pagamento' => trim((string) ($payment['chave_pagamento'] ?? '')),
            'valor_parcela' => $installmentValue,
        ];
    }

    private function normalizeNotes(array $notes): array
    {
        return [
            'interna' => trim((string) ($notes['interna'] ?? '')),
            'fiscal' => trim((string) ($notes['fiscal'] ?? '')),
        ];
    }

    private function buildPendencias(
        array $recipient,
        array $commercial,
        array $items,
        array $payment,
        Unidade $unit
    ): array {
        $pendencias = [];

        if (! $this->unitFiscalReady($unit)) {
            $pendencias[] = [
                'level' => 'critical',
                'message' => 'A unidade ainda nao possui configuracao fiscal completa para emissao.',
            ];
        }

        if (($recipient['tipo_pessoa'] ?? '') === 'pj' && trim((string) ($recipient['inscricao_estadual'] ?? '')) === '') {
            $pendencias[] = [
                'level' => 'warning',
                'message' => 'Cliente PJ sem inscricao estadual informada. Confirme se esta isento.',
            ];
        }

        if (($commercial['indicador_presenca'] ?? '') === 'internet'
            && ($payment['forma_pagamento'] ?? '') === 'dinheiro') {
            $pendencias[] = [
                'level' => 'warning',
                'message' => 'Venda pela internet com pagamento em dinheiro. Valide a regra comercial antes da emissao.',
            ];
        }

        foreach ($items as $index => $item) {
            if (trim((string) ($item['seguradora'] ?? '')) === '' || trim((string) ($item['ramo'] ?? '')) === '') {
                $pendencias[] = [
                    'level' => 'critical',
                    'message' => 'O item ' . ($index + 1) . ' esta sem seguradora ou ramo.',
                ];
            }

            if (trim((string) ($item['natureza_receita'] ?? '')) === '') {
                $pendencias[] = [
                    'level' => 'critical',
                    'message' => 'O item ' . ($index + 1) . ' esta sem natureza da receita.',
                ];
            }

            if ((bool) ($item['incide_iof'] ?? false) && ($item['aliquota_iof'] === null || (float) $item['aliquota_iof'] <= 0)) {
                $pendencias[] = [
                    'level' => 'critical',
                    'message' => 'O item ' . ($index + 1) . ' esta sem aliquota de IOF.',
                ];
            }

            if ((bool) ($item['incide_iof'] ?? false) && trim((string) ($item['regra_base_iof'] ?? '')) === '') {
                $pendencias[] = [
                    'level' => 'critical',
                    'message' => 'O item ' . ($index + 1) . ' esta sem regra de base do IOF.',
                ];
            }

            if ((bool) ($item['gera_nfse'] ?? false)) {
                if (trim((string) ($item['item_lista_servico'] ?? '')) === '') {
                    $pendencias[] = [
                        'level' => 'critical',
                        'message' => 'O item ' . ($index + 1) . ' esta sem item da lista de servico da NFS-e.',
                    ];
                }

                if (trim((string) ($item['municipio_iss'] ?? '')) === '' || trim((string) ($item['uf_iss'] ?? '')) === '') {
                    $pendencias[] = [
                        'level' => 'critical',
                        'message' => 'O item ' . ($index + 1) . ' esta sem municipio/UF de incidencia do ISS.',
                    ];
                }

                if ($item['aliquota_iss'] === null || (float) $item['aliquota_iss'] <= 0) {
                    $pendencias[] = [
                        'level' => 'critical',
                        'message' => 'O item ' . ($index + 1) . ' esta sem aliquota de ISS.',
                    ];
                }

                if (trim((string) ($item['prestador_nfse'] ?? '')) === '' || trim((string) ($item['tomador_nfse'] ?? '')) === '') {
                    $pendencias[] = [
                        'level' => 'critical',
                        'message' => 'O item ' . ($index + 1) . ' esta sem prestador ou tomador da NFS-e.',
                    ];
                }
            }

            if ((bool) ($item['ha_corretagem'] ?? false) && ! (bool) ($item['gera_nfse'] ?? false)) {
                $pendencias[] = [
                    'level' => 'warning',
                    'message' => 'O item ' . ($index + 1) . ' possui corretagem sem NFS-e habilitada. Confirme a regra operacional.',
                ];
            }
        }

        return $pendencias;
    }

    private function appendHistory(?NfeLaunch $existing, User $user, string $status): array
    {
        $history = collect($existing?->tb29_historico ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values();

        $history->push([
            'at' => now()->format('Y-m-d H:i:s'),
            'user_id' => (int) $user->id,
            'user_name' => (string) $user->name,
            'status' => $status,
            'action' => $existing ? 'update' : 'create',
        ]);

        return $history->all();
    }

    private function buildLaunchNumber(int $unitId, int $launchId): string
    {
        return sprintf('NFE-%04d-%06d', $unitId, $launchId);
    }

    private function serializeLaunchListItem(NfeLaunch $launch): array
    {
        $recipient = is_array($launch->tb29_destinatario) ? $launch->tb29_destinatario : [];
        $totals = is_array($launch->tb29_totais) ? $launch->tb29_totais : [];

        return [
            'id' => (int) $launch->tb29_id,
            'number' => (string) ($launch->tb29_numero ?? 'Sem numero'),
            'status' => (string) $launch->tb29_status,
            'status_label' => self::STATUS_LABELS[$launch->tb29_status] ?? $launch->tb29_status,
            'status_color' => self::STATUS_COLORS[$launch->tb29_status] ?? 'default',
            'operation_type' => self::OPERATION_TYPE_LABELS[$launch->tb29_tipo_operacao] ?? $launch->tb29_tipo_operacao,
            'launch_date' => optional($launch->tb29_data_lancamento)?->format('d/m/y'),
            'recipient_name' => (string) ($recipient['nome'] ?? '--'),
            'recipient_document' => (string) ($recipient['documento'] ?? '--'),
            'total' => (float) ($totals['total'] ?? 0),
            'unit_name' => (string) ($launch->unidade?->tb2_nome ?? '--'),
            'updated_at' => optional($launch->updated_at)?->format('d/m/y H:i'),
            'edit_url' => route('nfe.launches.edit', ['launch' => $launch->tb29_id, 'unit_id' => $launch->tb2_id]),
        ];
    }

    private function serializeLaunchForForm(NfeLaunch $launch): array
    {
        $recipient = is_array($launch->tb29_destinatario) ? $launch->tb29_destinatario : [];
        $commercial = is_array($launch->tb29_comercial) ? $launch->tb29_comercial : [];
        $items = is_array($launch->tb29_itens) ? $launch->tb29_itens : [];
        $payment = is_array($launch->tb29_pagamento) ? $launch->tb29_pagamento : [];
        $totals = is_array($launch->tb29_totais) ? $launch->tb29_totais : [];
        $pendencias = is_array($launch->tb29_pendencias) ? $launch->tb29_pendencias : [];
        $history = is_array($launch->tb29_historico) ? $launch->tb29_historico : [];
        $notes = json_decode((string) ($launch->tb29_observacoes ?? ''), true);

        return [
            'id' => (int) $launch->tb29_id,
            'number' => (string) ($launch->tb29_numero ?? ''),
            'unit_id' => (int) $launch->tb2_id,
            'status' => (string) $launch->tb29_status,
            'operation_type' => (string) $launch->tb29_tipo_operacao,
            'finality' => (string) ($launch->tb29_finalidade ?? ''),
            'launch_date' => optional($launch->tb29_data_lancamento)?->format('Y-m-d'),
            'issue_date' => optional($launch->tb29_data_emissao)?->format('Y-m-d'),
            'competence_date' => optional($launch->tb29_data_competencia)?->format('Y-m-d'),
            'recipient' => [
                'tipo_pessoa' => (string) ($recipient['tipo_pessoa'] ?? 'pf'),
                'nome' => (string) ($recipient['nome'] ?? ''),
                'documento' => (string) ($recipient['documento'] ?? ''),
                'inscricao_estadual' => (string) ($recipient['inscricao_estadual'] ?? ''),
                'email' => (string) ($recipient['email'] ?? ''),
                'telefone' => (string) ($recipient['telefone'] ?? ''),
                'cep' => (string) ($recipient['cep'] ?? ''),
                'logradouro' => (string) ($recipient['logradouro'] ?? ''),
                'numero' => (string) ($recipient['numero'] ?? ''),
                'complemento' => (string) ($recipient['complemento'] ?? ''),
                'bairro' => (string) ($recipient['bairro'] ?? ''),
                'cidade' => (string) ($recipient['cidade'] ?? ''),
                'uf' => (string) ($recipient['uf'] ?? ''),
            ],
            'commercial' => [
                'natureza_operacao' => (string) ($commercial['natureza_operacao'] ?? ''),
                'serie' => (string) ($commercial['serie'] ?? '1'),
                'indicador_presenca' => (string) ($commercial['indicador_presenca'] ?? 'presencial'),
                'vendedor' => (string) ($commercial['vendedor'] ?? ''),
                'informacoes_adicionais' => (string) ($commercial['informacoes_adicionais'] ?? ''),
            ],
            'items' => collect($items)->map(function (array $item) {
                return [
                    'produto_seguro_id' => $item['produto_seguro_id'] ? (string) $item['produto_seguro_id'] : '',
                    'codigo' => (string) ($item['codigo'] ?? ''),
                    'descricao' => (string) ($item['descricao'] ?? ''),
                    'seguradora' => (string) ($item['seguradora'] ?? ''),
                    'ramo' => (string) ($item['ramo'] ?? ''),
                    'modalidade' => (string) ($item['modalidade'] ?? ''),
                    'tipo_contratacao' => (string) ($item['tipo_contratacao'] ?? 'individual'),
                    'periodicidade' => (string) ($item['periodicidade'] ?? 'mensal'),
                    'natureza_receita' => (string) ($item['natureza_receita'] ?? 'premio de seguro'),
                    'ramo_fiscal' => (string) ($item['ramo_fiscal'] ?? 'seguro de danos'),
                    'incide_iof' => (bool) ($item['incide_iof'] ?? true),
                    'aliquota_iof' => $item['aliquota_iof'] !== null ? (string) $item['aliquota_iof'] : '',
                    'permite_override_iof' => (bool) ($item['permite_override_iof'] ?? true),
                    'regra_base_iof' => (string) ($item['regra_base_iof'] ?? 'premio'),
                    'destacar_iof' => (bool) ($item['destacar_iof'] ?? true),
                    'ha_corretagem' => (bool) ($item['ha_corretagem'] ?? false),
                    'gera_nfse' => (bool) ($item['gera_nfse'] ?? false),
                    'item_lista_servico' => (string) ($item['item_lista_servico'] ?? ''),
                    'codigo_servico_nfse' => (string) ($item['codigo_servico_nfse'] ?? ''),
                    'municipio_iss' => (string) ($item['municipio_iss'] ?? ''),
                    'uf_iss' => (string) ($item['uf_iss'] ?? ''),
                    'aliquota_iss' => $item['aliquota_iss'] !== null ? (string) $item['aliquota_iss'] : '',
                    'prestador_nfse' => (string) ($item['prestador_nfse'] ?? ''),
                    'tomador_nfse' => (string) ($item['tomador_nfse'] ?? ''),
                    'ncm' => (string) ($item['ncm'] ?? ''),
                    'cfop' => (string) ($item['cfop'] ?? ''),
                    'unidade' => (string) ($item['unidade'] ?? 'UN'),
                    'quantidade' => (string) ($item['quantidade'] ?? '1'),
                    'valor_unitario' => (string) ($item['valor_unitario'] ?? '0'),
                    'desconto' => (string) ($item['desconto'] ?? '0'),
                ];
            })->all(),
            'payment' => [
                'forma_pagamento' => (string) ($payment['forma_pagamento'] ?? 'dinheiro'),
                'parcelas' => (string) ($payment['parcelas'] ?? '1'),
                'primeiro_vencimento' => (string) ($payment['primeiro_vencimento'] ?? ''),
                'condicao_pagamento' => (string) ($payment['condicao_pagamento'] ?? ''),
                'chave_pagamento' => (string) ($payment['chave_pagamento'] ?? ''),
            ],
            'observacoes' => [
                'interna' => (string) ($notes['interna'] ?? ''),
                'fiscal' => (string) ($notes['fiscal'] ?? ''),
            ],
            'totals' => $totals,
            'pendencias' => $pendencias,
            'history' => $history,
        ];
    }

    private function defaultLaunchPayload(int $selectedUnitId, User $user): array
    {
        return [
            'id' => null,
            'number' => 'Gerado apos salvar',
            'unit_id' => $selectedUnitId > 0 ? $selectedUnitId : '',
            'status' => 'rascunho',
            'operation_type' => 'saida',
            'finality' => 'normal',
            'launch_date' => now()->format('Y-m-d'),
            'issue_date' => now()->format('Y-m-d'),
            'competence_date' => now()->format('Y-m-d'),
            'recipient' => [
                'tipo_pessoa' => 'pf',
                'nome' => '',
                'documento' => '',
                'inscricao_estadual' => '',
                'email' => '',
                'telefone' => '',
                'cep' => '',
                'logradouro' => '',
                'numero' => '',
                'complemento' => '',
                'bairro' => '',
                'cidade' => '',
                'uf' => '',
            ],
            'commercial' => [
                'natureza_operacao' => 'Venda de seguro',
                'serie' => '1',
                'indicador_presenca' => 'presencial',
                'vendedor' => (string) $user->name,
                'informacoes_adicionais' => '',
            ],
            'items' => [[
                'produto_seguro_id' => '',
                'codigo' => '',
                'descricao' => '',
                'seguradora' => '',
                'ramo' => '',
                'modalidade' => '',
                'tipo_contratacao' => 'individual',
                'periodicidade' => 'mensal',
                'natureza_receita' => 'premio de seguro',
                'ramo_fiscal' => 'seguro de danos',
                'incide_iof' => true,
                'aliquota_iof' => '7.38',
                'permite_override_iof' => true,
                'regra_base_iof' => 'premio',
                'destacar_iof' => true,
                'ha_corretagem' => false,
                'gera_nfse' => false,
                'item_lista_servico' => '10.01',
                'codigo_servico_nfse' => '',
                'municipio_iss' => '',
                'uf_iss' => '',
                'aliquota_iss' => '0',
                'prestador_nfse' => '',
                'tomador_nfse' => '',
                'ncm' => '',
                'cfop' => '',
                'unidade' => 'UN',
                'quantidade' => '1',
                'valor_unitario' => '0',
                'desconto' => '0',
            ]],
            'payment' => [
                'forma_pagamento' => 'dinheiro',
                'parcelas' => '1',
                'primeiro_vencimento' => now()->format('Y-m-d'),
                'condicao_pagamento' => '',
                'chave_pagamento' => '',
            ],
            'observacoes' => [
                'interna' => '',
                'fiscal' => '',
            ],
            'totals' => [
                'subtotal' => 0,
                'desconto' => 0,
                'total' => 0,
                'quantidade_itens' => 1,
            ],
            'pendencias' => [],
            'history' => [],
        ];
    }

    private function insuranceProductOptionsForUnit(?Unidade $unit): array
    {
        if (! $unit || (int) ($unit->matriz_id ?? 0) <= 0 || ! Schema::hasTable('tb30_nfe_produtos_seguro')) {
            return [];
        }

        return NfeInsuranceProduct::query()
            ->where('matriz_id', (int) $unit->matriz_id)
            ->where('tb30_status', 1)
            ->where(function ($builder) use ($unit) {
                $builder->whereNull('tb2_id')
                    ->orWhere('tb2_id', (int) $unit->tb2_id);
            })
            ->orderBy('tb30_nome')
            ->get([
                'tb30_id',
                'tb30_codigo',
                'tb30_nome',
                'tb30_seguradora',
                'tb30_ramo',
                'tb30_modalidade',
                'tb30_tipo_contratacao',
                'tb30_periodicidade',
                'tb30_natureza_receita',
                'tb30_ramo_fiscal',
                'tb30_incide_iof',
                'tb30_aliquota_iof',
                'tb30_permite_override_iof',
                'tb30_regra_base_iof',
                'tb30_destacar_iof',
                'tb30_ha_corretagem',
                'tb30_gera_nfse',
                'tb30_item_lista_servico',
                'tb30_codigo_servico_nfse',
                'tb30_municipio_iss',
                'tb30_uf_iss',
                'tb30_aliquota_iss',
                'tb30_prestador_nfse',
                'tb30_tomador_nfse',
                'tb30_cfop',
                'tb30_ncm',
                'tb30_unidade_padrao',
                'tb30_premio_base',
            ])
            ->map(fn (NfeInsuranceProduct $product) => [
                'id' => (int) $product->tb30_id,
                'code' => (string) $product->tb30_codigo,
                'name' => (string) $product->tb30_nome,
                'insurer' => (string) $product->tb30_seguradora,
                'branch' => (string) $product->tb30_ramo,
                'modality' => (string) ($product->tb30_modalidade ?? ''),
                'contractType' => (string) $product->tb30_tipo_contratacao,
                'periodicity' => (string) $product->tb30_periodicidade,
                'naturezaReceita' => (string) ($product->tb30_natureza_receita ?? 'premio de seguro'),
                'ramoFiscal' => (string) ($product->tb30_ramo_fiscal ?? 'seguro de danos'),
                'incideIof' => (bool) $product->tb30_incide_iof,
                'aliquotaIof' => (float) ($product->tb30_aliquota_iof ?? 0),
                'permiteOverrideIof' => (bool) $product->tb30_permite_override_iof,
                'regraBaseIof' => (string) ($product->tb30_regra_base_iof ?? 'premio'),
                'destacarIof' => (bool) $product->tb30_destacar_iof,
                'haCorretagem' => (bool) $product->tb30_ha_corretagem,
                'geraNfse' => (bool) $product->tb30_gera_nfse,
                'itemListaServico' => (string) ($product->tb30_item_lista_servico ?? ''),
                'codigoServicoNfse' => (string) ($product->tb30_codigo_servico_nfse ?? ''),
                'municipioIss' => (string) ($product->tb30_municipio_iss ?? ''),
                'ufIss' => (string) ($product->tb30_uf_iss ?? ''),
                'aliquotaIss' => (float) ($product->tb30_aliquota_iss ?? 0),
                'prestadorNfse' => (string) ($product->tb30_prestador_nfse ?? ''),
                'tomadorNfse' => (string) ($product->tb30_tomador_nfse ?? ''),
                'cfop' => (string) $product->tb30_cfop,
                'ncm' => (string) ($product->tb30_ncm ?? ''),
                'unit' => (string) $product->tb30_unidade_padrao,
                'price' => (float) ($product->tb30_premio_base ?? 0),
                'label' => sprintf(
                    '%s - %s (%s)',
                    (string) $product->tb30_codigo,
                    (string) $product->tb30_nome,
                    (string) $product->tb30_seguradora
                ),
            ])
            ->values()
            ->all();
    }

    private function unitFiscalReady(Unidade $unit): bool
    {
        $configuration = ConfiguracaoFiscal::query()
            ->where('tb2_id', $unit->tb2_id)
            ->first();

        return $configuration instanceof ConfiguracaoFiscal
            && (bool) $configuration->tb26_emitir_nfe
            && filled($configuration->tb26_uf)
            && filled($configuration->tb26_codigo_municipio)
            && filled($configuration->tb26_razao_social)
            && filled($configuration->tb26_certificado_arquivo);
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = collect(self::STATUS_LABELS)
            ->only(self::WRITABLE_STATUSES)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();

        if (! $includeAll) {
            return $options;
        }

        return array_merge([[
            'value' => 'all',
            'label' => 'Todos os status',
        ]], $options);
    }

    private function operationTypeOptions(): array
    {
        return collect(self::OPERATION_TYPE_LABELS)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function finalityOptions(): array
    {
        return collect(self::FINALITY_LABELS)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function paymentMethodOptions(): array
    {
        return collect(self::PAYMENT_METHOD_LABELS)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, [1, '1', 'true', 'on', 'yes'], true);
    }

    private function normalizeOptionalDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeOptionalUpperString(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}

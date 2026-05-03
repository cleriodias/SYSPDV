import AlertMessage from '@/Components/Alert/AlertMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    getBrazilTodayInputValue,
    isoToBrazilShortDateInput,
    normalizeBrazilShortDateInput,
    shortBrazilDateInputToIso,
} from '@/Utils/date';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useRef } from 'react';

const STATUS_STYLES = {
    rascunho: 'border-slate-200 bg-slate-100 text-slate-700',
    revisao: 'border-cyan-200 bg-cyan-50 text-cyan-700',
    pronto_emissao: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    cancelada: 'border-slate-300 bg-slate-200 text-slate-800',
    emitida: 'border-blue-200 bg-blue-50 text-blue-700',
};

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

const parseDecimal = (value) => {
    const normalized = String(value ?? '')
        .replace(',', '.')
        .replace(/[^\d.-]/g, '');

    const parsed = Number(normalized);

    return Number.isFinite(parsed) ? parsed : 0;
};

const createEmptyItem = () => ({
    produto_seguro_id: '',
    codigo: '',
    descricao: '',
    seguradora: '',
    ramo: '',
    modalidade: '',
    tipo_contratacao: 'individual',
    periodicidade: 'mensal',
    natureza_receita: 'premio de seguro',
    ramo_fiscal: 'seguro de danos',
    incide_iof: true,
    aliquota_iof: '7.38',
    permite_override_iof: true,
    regra_base_iof: 'premio',
    destacar_iof: true,
    ha_corretagem: false,
    gera_nfse: false,
    item_lista_servico: '10.01',
    codigo_servico_nfse: '',
    municipio_iss: '',
    uf_iss: '',
    aliquota_iss: '0',
    prestador_nfse: '',
    tomador_nfse: '',
    ncm: '',
    cfop: '',
    unidade: 'UN',
    quantidade: '1',
    valor_unitario: '0',
    desconto: '0',
});

const SectionCard = ({ title, description, children, defaultOpen = true }) => (
    <details open={defaultOpen} className="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <summary className="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5">
            <div>
                <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
                {description ? <p className="mt-1 text-sm text-slate-500">{description}</p> : null}
            </div>
            <span className="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 transition group-open:rotate-180">
                <i className="bi bi-chevron-down" aria-hidden="true"></i>
            </span>
        </summary>
        <div className="border-t border-slate-100 px-6 py-6">{children}</div>
    </details>
);

const FieldError = ({ message }) =>
    message ? <p className="mt-1 text-sm text-rose-600">{message}</p> : null;

const ShortDateInput = ({ id, label, value, onChange, error, required = false, min = '' }) => {
    const pickerRef = useRef(null);
    const isoValue = shortBrazilDateInputToIso(value);

    return (
        <div>
            <label htmlFor={id} className="text-sm font-semibold text-slate-700">
                {label}{required ? ' *' : ''}
            </label>
            <div className="relative mt-2">
                <input
                    id={id}
                    type="text"
                    inputMode="numeric"
                    value={value}
                    onChange={(event) => onChange(normalizeBrazilShortDateInput(event.target.value))}
                    placeholder="DD/MM/AA"
                    className="w-full rounded-2xl border border-slate-200 px-4 py-3 pr-14 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                />
                <button
                    type="button"
                    onClick={() => pickerRef.current?.showPicker?.()}
                    className="absolute inset-y-2 right-2 inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-slate-500 transition hover:bg-slate-100"
                    aria-label={`Selecionar ${label.toLowerCase()}`}
                >
                    <i className="bi bi-calendar3" aria-hidden="true"></i>
                </button>
                <input
                    ref={pickerRef}
                    type="date"
                    value={isoValue}
                    min={min}
                    onChange={(event) => onChange(isoToBrazilShortDateInput(event.target.value))}
                    className="pointer-events-none absolute inset-0 h-full w-full opacity-0"
                    tabIndex={-1}
                    aria-hidden="true"
                />
            </div>
            <FieldError message={error} />
        </div>
    );
};

const buildInitialData = (launch) => ({
    unit_id: launch?.unit_id ? String(launch.unit_id) : '',
    status: launch?.status ?? 'rascunho',
    operation_type: launch?.operation_type ?? 'saida',
    finality: launch?.finality ?? 'normal',
    launch_date: isoToBrazilShortDateInput(launch?.launch_date ?? getBrazilTodayInputValue()),
    issue_date: isoToBrazilShortDateInput(launch?.issue_date ?? getBrazilTodayInputValue()),
    competence_date: isoToBrazilShortDateInput(launch?.competence_date ?? getBrazilTodayInputValue()),
    recipient: {
        tipo_pessoa: launch?.recipient?.tipo_pessoa ?? 'pf',
        nome: launch?.recipient?.nome ?? '',
        documento: launch?.recipient?.documento ?? '',
        inscricao_estadual: launch?.recipient?.inscricao_estadual ?? '',
        email: launch?.recipient?.email ?? '',
        telefone: launch?.recipient?.telefone ?? '',
        cep: launch?.recipient?.cep ?? '',
        logradouro: launch?.recipient?.logradouro ?? '',
        numero: launch?.recipient?.numero ?? '',
        complemento: launch?.recipient?.complemento ?? '',
        bairro: launch?.recipient?.bairro ?? '',
        cidade: launch?.recipient?.cidade ?? '',
        uf: launch?.recipient?.uf ?? '',
    },
    commercial: {
        natureza_operacao: launch?.commercial?.natureza_operacao ?? '',
        serie: launch?.commercial?.serie ?? '1',
        indicador_presenca: launch?.commercial?.indicador_presenca ?? 'presencial',
        vendedor: launch?.commercial?.vendedor ?? '',
        informacoes_adicionais: launch?.commercial?.informacoes_adicionais ?? '',
    },
    items: Array.isArray(launch?.items) && launch.items.length
        ? launch.items.map((item) => ({
            produto_seguro_id: item?.produto_seguro_id ? String(item.produto_seguro_id) : '',
            codigo: item?.codigo ?? '',
            descricao: item?.descricao ?? '',
            seguradora: item?.seguradora ?? '',
            ramo: item?.ramo ?? '',
            modalidade: item?.modalidade ?? '',
            tipo_contratacao: item?.tipo_contratacao ?? 'individual',
            periodicidade: item?.periodicidade ?? 'mensal',
            natureza_receita: item?.natureza_receita ?? 'premio de seguro',
            ramo_fiscal: item?.ramo_fiscal ?? 'seguro de danos',
            incide_iof: item?.incide_iof ?? true,
            aliquota_iof: item?.aliquota_iof ?? '7.38',
            permite_override_iof: item?.permite_override_iof ?? true,
            regra_base_iof: item?.regra_base_iof ?? 'premio',
            destacar_iof: item?.destacar_iof ?? true,
            ha_corretagem: item?.ha_corretagem ?? false,
            gera_nfse: item?.gera_nfse ?? false,
            item_lista_servico: item?.item_lista_servico ?? '10.01',
            codigo_servico_nfse: item?.codigo_servico_nfse ?? '',
            municipio_iss: item?.municipio_iss ?? '',
            uf_iss: item?.uf_iss ?? '',
            aliquota_iss: item?.aliquota_iss ?? '0',
            prestador_nfse: item?.prestador_nfse ?? '',
            tomador_nfse: item?.tomador_nfse ?? '',
            ncm: item?.ncm ?? '',
            cfop: item?.cfop ?? '',
            unidade: item?.unidade ?? 'UN',
            quantidade: String(item?.quantidade ?? '1'),
            valor_unitario: String(item?.valor_unitario ?? '0'),
            desconto: String(item?.desconto ?? '0'),
        }))
        : [createEmptyItem()],
    payment: {
        forma_pagamento: launch?.payment?.forma_pagamento ?? 'dinheiro',
        parcelas: String(launch?.payment?.parcelas ?? '1'),
        primeiro_vencimento: isoToBrazilShortDateInput(launch?.payment?.primeiro_vencimento ?? getBrazilTodayInputValue()),
        condicao_pagamento: launch?.payment?.condicao_pagamento ?? '',
        chave_pagamento: launch?.payment?.chave_pagamento ?? '',
    },
    observacoes: {
        interna: launch?.observacoes?.interna ?? '',
        fiscal: launch?.observacoes?.fiscal ?? '',
    },
});

export default function Form({
    auth,
    mode,
    units = [],
    selectedUnitId = null,
    statusOptions = [],
    operationTypeOptions = [],
    finalityOptions = [],
    paymentMethodOptions = [],
    launch,
    insuranceProducts = [],
    fiscalReady = false,
}) {
    const { flash } = usePage().props;
    const isEditing = mode === 'edit';
    const initialData = useMemo(() => buildInitialData(launch), [launch]);
    const {
        data,
        setData,
        processing,
        errors,
        transform,
        post,
        put,
    } = useForm(initialData);
    const lockedStatuses = ['cancelada', 'emitida'];

    const insuranceProductMap = useMemo(
        () => new Map(insuranceProducts.map((product) => [String(product.id), product])),
        [insuranceProducts],
    );

    const computedTotals = useMemo(() => {
        const subtotal = data.items.reduce((sum, item) => {
            const quantity = parseDecimal(item.quantidade);
            const unitPrice = parseDecimal(item.valor_unitario);

            return sum + (quantity * unitPrice);
        }, 0);

        const discount = data.items.reduce((sum, item) => sum + parseDecimal(item.desconto), 0);
        const total = Math.max(subtotal - discount, 0);
        const installments = Math.max(Number(data.payment.parcelas || 1), 1);

        return {
            subtotal,
            discount,
            total,
            installmentValue: installments > 0 ? total / installments : total,
        };
    }, [data.items, data.payment.parcelas]);

    const localPendencias = useMemo(() => {
        const pending = [];

        if (!fiscalReady) {
            pending.push({
                level: 'critical',
                message: 'Unidade sem configuracao fiscal completa para emissao.',
            });
        }

        if (!data.recipient.documento) {
            pending.push({
                level: 'critical',
                message: 'Documento do destinatario nao informado.',
            });
        }

        data.items.forEach((item, index) => {
            if (!item.seguradora || !item.ramo) {
                pending.push({
                    level: 'critical',
                    message: `Item ${index + 1} sem seguradora ou ramo.`,
                });
            }

            if (!item.natureza_receita) {
                pending.push({
                    level: 'critical',
                    message: `Item ${index + 1} sem natureza da receita.`,
                });
            }

            if (item.incide_iof && !parseDecimal(item.aliquota_iof)) {
                pending.push({
                    level: 'critical',
                    message: `Item ${index + 1} sem aliquota de IOF.`,
                });
            }

            if (item.incide_iof && !item.regra_base_iof) {
                pending.push({
                    level: 'critical',
                    message: `Item ${index + 1} sem regra de base do IOF.`,
                });
            }

            if (item.gera_nfse) {
                if (!item.item_lista_servico) {
                    pending.push({
                        level: 'critical',
                        message: `Item ${index + 1} sem item da lista de servico da NFS-e.`,
                    });
                }

                if (!item.municipio_iss || !item.uf_iss) {
                    pending.push({
                        level: 'critical',
                        message: `Item ${index + 1} sem municipio/UF de incidencia do ISS.`,
                    });
                }

                if (!parseDecimal(item.aliquota_iss)) {
                    pending.push({
                        level: 'critical',
                        message: `Item ${index + 1} sem aliquota de ISS.`,
                    });
                }

                if (!item.prestador_nfse || !item.tomador_nfse) {
                    pending.push({
                        level: 'critical',
                        message: `Item ${index + 1} sem prestador ou tomador da NFS-e.`,
                    });
                }
            }

            if (item.ha_corretagem && !item.gera_nfse) {
                pending.push({
                    level: 'warning',
                    message: `Item ${index + 1} com corretagem sem NFS-e habilitada.`,
                });
            }
        });

        if (Number(data.payment.parcelas || 0) > 1 && !data.payment.primeiro_vencimento) {
            pending.push({
                level: 'warning',
                message: 'Parcelamento configurado sem primeiro vencimento.',
            });
        }

        return pending;
    }, [data.items, data.payment.parcelas, data.payment.primeiro_vencimento, data.recipient.documento, fiscalReady]);

    const selectedUnit = units.find((unit) => Number(unit.id) === Number(data.unit_id || selectedUnitId));
    const isLocked = isEditing && lockedStatuses.includes(launch?.status ?? '');

    const updateNestedField = (section, field, value) => {
        setData(section, {
            ...data[section],
            [field]: value,
        });
    };

    const updateItem = (index, field, value) => {
        setData('items', data.items.map((item, itemIndex) => (
            itemIndex === index ? { ...item, [field]: value } : item
        )));
    };

    const handleInsuranceProductChange = (index, productId) => {
        const product = insuranceProductMap.get(String(productId));

        if (!product) {
            updateItem(index, 'produto_seguro_id', '');
            return;
        }

        setData('items', data.items.map((item, itemIndex) => (
            itemIndex === index
                ? {
                    ...item,
                    produto_seguro_id: String(product.id),
                    codigo: String(product.code),
                    descricao: product.name,
                    seguradora: product.insurer,
                    ramo: product.branch,
                    modalidade: product.modality ?? '',
                    tipo_contratacao: product.contractType ?? 'individual',
                    periodicidade: product.periodicity ?? 'mensal',
                    natureza_receita: product.naturezaReceita ?? 'premio de seguro',
                    ramo_fiscal: product.ramoFiscal ?? 'seguro de danos',
                    incide_iof: product.incideIof ?? true,
                    aliquota_iof: String(product.aliquotaIof ?? 0),
                    permite_override_iof: product.permiteOverrideIof ?? true,
                    regra_base_iof: product.regraBaseIof ?? 'premio',
                    destacar_iof: product.destacarIof ?? true,
                    ha_corretagem: product.haCorretagem ?? false,
                    gera_nfse: product.geraNfse ?? false,
                    item_lista_servico: product.itemListaServico ?? '10.01',
                    codigo_servico_nfse: product.codigoServicoNfse ?? '',
                    municipio_iss: product.municipioIss ?? '',
                    uf_iss: product.ufIss ?? '',
                    aliquota_iss: String(product.aliquotaIss ?? 0),
                    prestador_nfse: product.prestadorNfse ?? '',
                    tomador_nfse: product.tomadorNfse ?? '',
                    ncm: product.ncm ?? '',
                    cfop: product.cfop ?? '',
                    unidade: product.unit ?? 'UN',
                    valor_unitario: String(product.price ?? 0),
                }
                : item
        )));
    };

    const addItem = () => {
        setData('items', [...data.items, createEmptyItem()]);
    };

    const removeItem = (index) => {
        if (data.items.length === 1) {
            setData('items', [createEmptyItem()]);
            return;
        }

        setData('items', data.items.filter((_, itemIndex) => itemIndex !== index));
    };

    const buildPayload = (status) => ({
        tb2_id: Number(data.unit_id),
        tb29_status: status,
        tb29_tipo_operacao: data.operation_type,
        tb29_finalidade: data.finality || null,
        tb29_data_lancamento: shortBrazilDateInputToIso(data.launch_date),
        tb29_data_emissao: shortBrazilDateInputToIso(data.issue_date),
        tb29_data_competencia: shortBrazilDateInputToIso(data.competence_date),
        destinatario: {
            tipo_pessoa: data.recipient.tipo_pessoa,
            nome: data.recipient.nome,
            documento: data.recipient.documento,
            inscricao_estadual: data.recipient.inscricao_estadual,
            email: data.recipient.email,
            telefone: data.recipient.telefone,
            cep: data.recipient.cep,
            logradouro: data.recipient.logradouro,
            numero: data.recipient.numero,
            complemento: data.recipient.complemento,
            bairro: data.recipient.bairro,
            cidade: data.recipient.cidade,
            uf: data.recipient.uf,
        },
        comercial: {
            natureza_operacao: data.commercial.natureza_operacao,
            serie: data.commercial.serie,
            indicador_presenca: data.commercial.indicador_presenca,
            vendedor: data.commercial.vendedor,
            informacoes_adicionais: data.commercial.informacoes_adicionais,
        },
        itens: data.items.map((item) => ({
            produto_seguro_id: item.produto_seguro_id ? Number(item.produto_seguro_id) : null,
            codigo: item.codigo,
            descricao: item.descricao,
            seguradora: item.seguradora,
            ramo: item.ramo,
            modalidade: item.modalidade,
            tipo_contratacao: item.tipo_contratacao,
            periodicidade: item.periodicidade,
            natureza_receita: item.natureza_receita,
            ramo_fiscal: item.ramo_fiscal,
            incide_iof: !!item.incide_iof,
            aliquota_iof: parseDecimal(item.aliquota_iof),
            permite_override_iof: !!item.permite_override_iof,
            regra_base_iof: item.regra_base_iof,
            destacar_iof: !!item.destacar_iof,
            ha_corretagem: !!item.ha_corretagem,
            gera_nfse: !!item.gera_nfse,
            item_lista_servico: item.item_lista_servico,
            codigo_servico_nfse: item.codigo_servico_nfse,
            municipio_iss: item.municipio_iss,
            uf_iss: item.uf_iss,
            aliquota_iss: parseDecimal(item.aliquota_iss),
            prestador_nfse: item.prestador_nfse,
            tomador_nfse: item.tomador_nfse,
            ncm: item.ncm,
            cfop: item.cfop,
            unidade: item.unidade,
            quantidade: parseDecimal(item.quantidade),
            valor_unitario: parseDecimal(item.valor_unitario),
            desconto: parseDecimal(item.desconto),
        })),
        pagamento: {
            forma_pagamento: data.payment.forma_pagamento,
            parcelas: Number(data.payment.parcelas || 1),
            primeiro_vencimento: shortBrazilDateInputToIso(data.payment.primeiro_vencimento),
            condicao_pagamento: data.payment.condicao_pagamento,
            chave_pagamento: data.payment.chave_pagamento,
        },
        observacoes: {
            interna: data.observacoes.interna,
            fiscal: data.observacoes.fiscal,
        },
    });

    const submitWithStatus = (status) => {
        transform(() => buildPayload(status));

        if (isEditing) {
            put(route('nfe.launches.update', { launch: launch.id }), {
                preserveScroll: true,
            });
            return;
        }

        post(route('nfe.launches.store'), {
            preserveScroll: true,
        });
    };

    const handleCreateUnitChange = (nextUnitId) => {
        setData('unit_id', nextUnitId);

        if (!nextUnitId) {
            return;
        }

        router.get(route('nfe.launches.create'), {
            unit_id: nextUnitId,
        }, {
            replace: true,
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {isEditing ? 'Editar lancamento NFe' : 'Novo lancamento NFe'}
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Fluxo preparado para a NFe - Corretora de Seguros antes da emissao fiscal.
                    </p>
                </div>
            )}
        >
            <Head title={isEditing ? 'Editar lancamento NFe - Corretora de Seguros' : 'Novo lancamento NFe - Corretora de Seguros'} />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <section className="sticky top-4 z-20 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl">
                        <div className="border-b border-slate-100 bg-gradient-to-r from-slate-950 via-slate-900 to-sky-900 px-6 py-5 text-white">
                            <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div className="space-y-2">
                                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-slate-200">Lancamento NFe</p>
                                    <h1 className="text-3xl font-semibold tracking-tight">
                                        {launch?.number ?? 'Gerado apos salvar'}
                                    </h1>
                                    <p className="text-sm text-slate-200">
                                        Unidade: {selectedUnit?.name ?? 'Selecione a unidade'} | Status atual:{' '}
                                        <span className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${STATUS_STYLES[data.status] ?? STATUS_STYLES.rascunho}`}>
                                            {statusOptions.find((option) => option.value === data.status)?.label ?? data.status}
                                        </span>
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-3">
                                    <Link
                                        href={route('nfe.launches.index', selectedUnitId ? { unit_id: selectedUnitId } : {})}
                                        className="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                                    >
                                        Voltar para a lista
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-4 px-6 py-5 lg:grid-cols-4">
                            <div>
                                <label className="text-sm font-semibold text-slate-700">Unidade</label>
                                <select
                                    value={data.unit_id}
                                    onChange={(event) => {
                                        if (isEditing) {
                                            setData('unit_id', event.target.value);
                                            return;
                                        }

                                        handleCreateUnitChange(event.target.value);
                                    }}
                                    disabled={isEditing}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:bg-slate-100 disabled:text-slate-500"
                                >
                                    <option value="">Selecione</option>
                                    {units.map((unit) => (
                                        <option key={unit.id} value={unit.id}>
                                            {unit.name}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb2_id} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Tipo de operacao</label>
                                <select
                                    value={data.operation_type}
                                    onChange={(event) => setData('operation_type', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {operationTypeOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb29_tipo_operacao} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Finalidade</label>
                                <select
                                    value={data.finality}
                                    onChange={(event) => setData('finality', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {finalityOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb29_finalidade} />
                            </div>

                            <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Configuracao fiscal</p>
                                <p className="mt-2 text-sm font-semibold text-slate-900">
                                    {fiscalReady ? 'Pronta para emissao' : 'Pendente'}
                                </p>
                                <p className="mt-1 text-xs text-slate-500">
                                    O painel lateral mostra as pendencias para fechar o lancamento.
                                </p>
                            </div>
                        </div>
                    </section>

                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_360px]">
                        <div className="space-y-6">
                            <SectionCard
                                title="Identificacao do lancamento"
                                description="Numero, datas e marcadores principais da operacao."
                            >
                                <div className="grid gap-4 md:grid-cols-3">
                                    <ShortDateInput
                                        id="launch-date"
                                        label="Data do lancamento"
                                        value={data.launch_date}
                                        onChange={(value) => setData('launch_date', value)}
                                        error={errors.tb29_data_lancamento}
                                        required
                                    />
                                    <ShortDateInput
                                        id="issue-date"
                                        label="Data de emissao"
                                        value={data.issue_date}
                                        onChange={(value) => setData('issue_date', value)}
                                        error={errors.tb29_data_emissao}
                                    />
                                    <ShortDateInput
                                        id="competence-date"
                                        label="Data de competencia"
                                        value={data.competence_date}
                                        onChange={(value) => setData('competence_date', value)}
                                        error={errors.tb29_data_competencia}
                                    />
                                </div>
                            </SectionCard>

                            <SectionCard
                                title="Dados do destinatario"
                                description="Cadastro do cliente para a nota fiscal com endereco completo."
                            >
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Tipo de pessoa</label>
                                        <select
                                            value={data.recipient.tipo_pessoa}
                                            onChange={(event) => updateNestedField('recipient', 'tipo_pessoa', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        >
                                            <option value="pf">Pessoa fisica</option>
                                            <option value="pj">Pessoa juridica</option>
                                        </select>
                                        <FieldError message={errors['destinatario.tipo_pessoa']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Documento</label>
                                        <input
                                            type="text"
                                            value={data.recipient.documento}
                                            onChange={(event) => updateNestedField('recipient', 'documento', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                            placeholder={data.recipient.tipo_pessoa === 'pj' ? 'CNPJ' : 'CPF'}
                                        />
                                        <FieldError message={errors['destinatario.documento']} />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="text-sm font-semibold text-slate-700">Nome / razao social</label>
                                        <input
                                            type="text"
                                            value={data.recipient.nome}
                                            onChange={(event) => updateNestedField('recipient', 'nome', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.nome']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Inscricao estadual</label>
                                        <input
                                            type="text"
                                            value={data.recipient.inscricao_estadual}
                                            onChange={(event) => updateNestedField('recipient', 'inscricao_estadual', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.inscricao_estadual']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Telefone</label>
                                        <input
                                            type="text"
                                            value={data.recipient.telefone}
                                            onChange={(event) => updateNestedField('recipient', 'telefone', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.telefone']} />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="text-sm font-semibold text-slate-700">E-mail</label>
                                        <input
                                            type="email"
                                            value={data.recipient.email}
                                            onChange={(event) => updateNestedField('recipient', 'email', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.email']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">CEP</label>
                                        <input
                                            type="text"
                                            value={data.recipient.cep}
                                            onChange={(event) => updateNestedField('recipient', 'cep', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.cep']} />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="text-sm font-semibold text-slate-700">Logradouro</label>
                                        <input
                                            type="text"
                                            value={data.recipient.logradouro}
                                            onChange={(event) => updateNestedField('recipient', 'logradouro', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.logradouro']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Numero</label>
                                        <input
                                            type="text"
                                            value={data.recipient.numero}
                                            onChange={(event) => updateNestedField('recipient', 'numero', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.numero']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Complemento</label>
                                        <input
                                            type="text"
                                            value={data.recipient.complemento}
                                            onChange={(event) => updateNestedField('recipient', 'complemento', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.complemento']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Bairro</label>
                                        <input
                                            type="text"
                                            value={data.recipient.bairro}
                                            onChange={(event) => updateNestedField('recipient', 'bairro', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.bairro']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Cidade</label>
                                        <input
                                            type="text"
                                            value={data.recipient.cidade}
                                            onChange={(event) => updateNestedField('recipient', 'cidade', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.cidade']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">UF</label>
                                        <input
                                            type="text"
                                            maxLength={2}
                                            value={data.recipient.uf}
                                            onChange={(event) => updateNestedField('recipient', 'uf', event.target.value.toUpperCase())}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm uppercase text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['destinatario.uf']} />
                                    </div>
                                </div>
                            </SectionCard>

                            <SectionCard
                                title="Dados comerciais e fiscais"
                                description="Parametros da operacao, serie e informacoes de presenca."
                            >
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Natureza da operacao</label>
                                        <input
                                            type="text"
                                            value={data.commercial.natureza_operacao}
                                            onChange={(event) => updateNestedField('commercial', 'natureza_operacao', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['comercial.natureza_operacao']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Serie</label>
                                        <input
                                            type="text"
                                            value={data.commercial.serie}
                                            onChange={(event) => updateNestedField('commercial', 'serie', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['comercial.serie']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Indicador de presenca</label>
                                        <select
                                            value={data.commercial.indicador_presenca}
                                            onChange={(event) => updateNestedField('commercial', 'indicador_presenca', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        >
                                            <option value="presencial">Presencial</option>
                                            <option value="internet">Internet</option>
                                            <option value="telefone">Telefone</option>
                                            <option value="nao_se_aplica">Nao se aplica</option>
                                        </select>
                                        <FieldError message={errors['comercial.indicador_presenca']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Vendedor responsavel</label>
                                        <input
                                            type="text"
                                            value={data.commercial.vendedor}
                                            onChange={(event) => updateNestedField('commercial', 'vendedor', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['comercial.vendedor']} />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="text-sm font-semibold text-slate-700">Informacoes adicionais</label>
                                        <textarea
                                            value={data.commercial.informacoes_adicionais}
                                            onChange={(event) => updateNestedField('commercial', 'informacoes_adicionais', event.target.value)}
                                            rows={4}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['comercial.informacoes_adicionais']} />
                                    </div>
                                </div>
                            </SectionCard>

                            <SectionCard
                                title="Produtos de seguro do lancamento"
                                description="Selecione o produto da carteira e complemente os dados comerciais do item."
                            >
                                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                                    <p className="text-sm text-slate-500">
                                        O catalogo abaixo usa o cadastro proprio de seguros da aplicacao NFe.
                                    </p>
                                    <Link
                                        href={route('nfe.insurance-products.create', data.unit_id ? { unit_id: data.unit_id } : {})}
                                        className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100"
                                    >
                                        Cadastrar produto de seguro
                                    </Link>
                                </div>

                                <div className="space-y-5">
                                    {data.items.map((item, index) => (
                                        <div key={`item-${index}`} className="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                            <div className="mb-4 flex items-center justify-between gap-3">
                                                <div>
                                                    <h4 className="text-sm font-semibold text-slate-900">Item {index + 1}</h4>
                                                    <p className="text-xs text-slate-500">Produto de seguro, seguradora, ramo e dados fiscais do item.</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeItem(index)}
                                                    className="inline-flex items-center rounded-full border border-rose-200 bg-white px-3 py-1 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                                >
                                                    Remover
                                                </button>
                                            </div>

                                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                <div className="xl:col-span-2">
                                                    <label className="text-sm font-semibold text-slate-700">Produto de seguro</label>
                                                    <select
                                                        value={item.produto_seguro_id}
                                                        onChange={(event) => handleInsuranceProductChange(index, event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="">Preenchimento manual</option>
                                                        {insuranceProducts.map((product) => (
                                                            <option key={product.id} value={product.id}>
                                                                {product.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <FieldError message={errors[`itens.${index}.produto_seguro_id`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Codigo</label>
                                                    <input
                                                        type="text"
                                                        value={item.codigo}
                                                        onChange={(event) => updateItem(index, 'codigo', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.codigo`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Descricao</label>
                                                    <input
                                                        type="text"
                                                        value={item.descricao}
                                                        onChange={(event) => updateItem(index, 'descricao', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.descricao`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Seguradora</label>
                                                    <input
                                                        type="text"
                                                        value={item.seguradora}
                                                        onChange={(event) => updateItem(index, 'seguradora', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.seguradora`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Ramo</label>
                                                    <input
                                                        type="text"
                                                        value={item.ramo}
                                                        onChange={(event) => updateItem(index, 'ramo', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.ramo`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Modalidade</label>
                                                    <input
                                                        type="text"
                                                        value={item.modalidade}
                                                        onChange={(event) => updateItem(index, 'modalidade', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.modalidade`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Tipo de contratacao</label>
                                                    <select
                                                        value={item.tipo_contratacao}
                                                        onChange={(event) => updateItem(index, 'tipo_contratacao', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="individual">Individual</option>
                                                        <option value="coletiva">Coletiva</option>
                                                        <option value="mensal">Mensal</option>
                                                        <option value="anual">Anual</option>
                                                    </select>
                                                    <FieldError message={errors[`itens.${index}.tipo_contratacao`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Periodicidade</label>
                                                    <select
                                                        value={item.periodicidade}
                                                        onChange={(event) => updateItem(index, 'periodicidade', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="mensal">Mensal</option>
                                                        <option value="trimestral">Trimestral</option>
                                                        <option value="semestral">Semestral</option>
                                                        <option value="anual">Anual</option>
                                                        <option value="unica">Parcela unica</option>
                                                    </select>
                                                    <FieldError message={errors[`itens.${index}.periodicidade`]} />
                                                </div>

                                                <div className="xl:col-span-2">
                                                    <label className="text-sm font-semibold text-slate-700">Natureza da receita</label>
                                                    <input
                                                        type="text"
                                                        value={item.natureza_receita}
                                                        onChange={(event) => updateItem(index, 'natureza_receita', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.natureza_receita`]} />
                                                </div>

                                                <div className="xl:col-span-2">
                                                    <label className="text-sm font-semibold text-slate-700">Ramo fiscal</label>
                                                    <input
                                                        type="text"
                                                        value={item.ramo_fiscal}
                                                        onChange={(event) => updateItem(index, 'ramo_fiscal', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.ramo_fiscal`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Incide IOF</label>
                                                    <select
                                                        value={item.incide_iof ? '1' : '0'}
                                                        onChange={(event) => updateItem(index, 'incide_iof', event.target.value === '1')}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="1">Sim</option>
                                                        <option value="0">Nao</option>
                                                    </select>
                                                    <FieldError message={errors[`itens.${index}.incide_iof`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Aliquota de IOF (%)</label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        value={item.aliquota_iof}
                                                        onChange={(event) => updateItem(index, 'aliquota_iof', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.aliquota_iof`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Permite override do IOF</label>
                                                    <select
                                                        value={item.permite_override_iof ? '1' : '0'}
                                                        onChange={(event) => updateItem(index, 'permite_override_iof', event.target.value === '1')}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="1">Sim</option>
                                                        <option value="0">Nao</option>
                                                    </select>
                                                    <FieldError message={errors[`itens.${index}.permite_override_iof`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Destacar IOF</label>
                                                    <select
                                                        value={item.destacar_iof ? '1' : '0'}
                                                        onChange={(event) => updateItem(index, 'destacar_iof', event.target.value === '1')}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="1">Sim</option>
                                                        <option value="0">Nao</option>
                                                    </select>
                                                    <FieldError message={errors[`itens.${index}.destacar_iof`]} />
                                                </div>

                                                <div className="xl:col-span-2">
                                                    <label className="text-sm font-semibold text-slate-700">Regra de base do IOF</label>
                                                    <input
                                                        type="text"
                                                        value={item.regra_base_iof}
                                                        onChange={(event) => updateItem(index, 'regra_base_iof', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.regra_base_iof`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Ha corretagem</label>
                                                    <select
                                                        value={item.ha_corretagem ? '1' : '0'}
                                                        onChange={(event) => updateItem(index, 'ha_corretagem', event.target.value === '1')}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="1">Sim</option>
                                                        <option value="0">Nao</option>
                                                    </select>
                                                    <FieldError message={errors[`itens.${index}.ha_corretagem`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Gera NFS-e</label>
                                                    <select
                                                        value={item.gera_nfse ? '1' : '0'}
                                                        onChange={(event) => updateItem(index, 'gera_nfse', event.target.value === '1')}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    >
                                                        <option value="1">Sim</option>
                                                        <option value="0">Nao</option>
                                                    </select>
                                                    <FieldError message={errors[`itens.${index}.gera_nfse`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Item lista servico</label>
                                                    <input
                                                        type="text"
                                                        value={item.item_lista_servico}
                                                        onChange={(event) => updateItem(index, 'item_lista_servico', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.item_lista_servico`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Codigo servico NFS-e</label>
                                                    <input
                                                        type="text"
                                                        value={item.codigo_servico_nfse}
                                                        onChange={(event) => updateItem(index, 'codigo_servico_nfse', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.codigo_servico_nfse`]} />
                                                </div>

                                                <div className="xl:col-span-2">
                                                    <label className="text-sm font-semibold text-slate-700">Municipio de incidencia do ISS</label>
                                                    <input
                                                        type="text"
                                                        value={item.municipio_iss}
                                                        onChange={(event) => updateItem(index, 'municipio_iss', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.municipio_iss`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">UF ISS</label>
                                                    <input
                                                        type="text"
                                                        maxLength={2}
                                                        value={item.uf_iss}
                                                        onChange={(event) => updateItem(index, 'uf_iss', event.target.value.toUpperCase())}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm uppercase text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.uf_iss`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Aliquota de ISS (%)</label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        value={item.aliquota_iss}
                                                        onChange={(event) => updateItem(index, 'aliquota_iss', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.aliquota_iss`]} />
                                                </div>

                                                <div className="xl:col-span-2">
                                                    <label className="text-sm font-semibold text-slate-700">Prestador da NFS-e</label>
                                                    <input
                                                        type="text"
                                                        value={item.prestador_nfse}
                                                        onChange={(event) => updateItem(index, 'prestador_nfse', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.prestador_nfse`]} />
                                                </div>

                                                <div className="xl:col-span-2">
                                                    <label className="text-sm font-semibold text-slate-700">Tomador da NFS-e</label>
                                                    <input
                                                        type="text"
                                                        value={item.tomador_nfse}
                                                        onChange={(event) => updateItem(index, 'tomador_nfse', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.tomador_nfse`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">CFOP opcional</label>
                                                    <input
                                                        type="text"
                                                        value={item.cfop}
                                                        onChange={(event) => updateItem(index, 'cfop', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.cfop`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">NCM opcional</label>
                                                    <input
                                                        type="text"
                                                        value={item.ncm}
                                                        onChange={(event) => updateItem(index, 'ncm', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.ncm`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Unidade</label>
                                                    <input
                                                        type="text"
                                                        value={item.unidade}
                                                        onChange={(event) => updateItem(index, 'unidade', event.target.value.toUpperCase())}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm uppercase text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.unidade`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Quantidade</label>
                                                    <input
                                                        type="number"
                                                        min="0.0001"
                                                        step="0.0001"
                                                        value={item.quantidade}
                                                        onChange={(event) => updateItem(index, 'quantidade', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.quantidade`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Premio / valor unitario</label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={item.valor_unitario}
                                                        onChange={(event) => updateItem(index, 'valor_unitario', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.valor_unitario`]} />
                                                </div>

                                                <div>
                                                    <label className="text-sm font-semibold text-slate-700">Desconto</label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={item.desconto}
                                                        onChange={(event) => updateItem(index, 'desconto', event.target.value)}
                                                        className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                                    />
                                                    <FieldError message={errors[`itens.${index}.desconto`]} />
                                                </div>
                                            </div>
                                        </div>
                                    ))}

                                    <button
                                        type="button"
                                        onClick={addItem}
                                        className="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700"
                                    >
                                        Adicionar item
                                    </button>
                                </div>
                            </SectionCard>

                            <SectionCard
                                title="Pagamento e fechamento"
                                description="Forma de pagamento, parcelas e vencimento inicial."
                            >
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Forma de pagamento</label>
                                        <select
                                            value={data.payment.forma_pagamento}
                                            onChange={(event) => updateNestedField('payment', 'forma_pagamento', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        >
                                            {paymentMethodOptions.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        <FieldError message={errors['pagamento.forma_pagamento']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Parcelas</label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="36"
                                            value={data.payment.parcelas}
                                            onChange={(event) => updateNestedField('payment', 'parcelas', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['pagamento.parcelas']} />
                                    </div>

                                    <ShortDateInput
                                        id="first-due-date"
                                        label="Primeiro vencimento"
                                        value={data.payment.primeiro_vencimento}
                                        onChange={(value) => updateNestedField('payment', 'primeiro_vencimento', value)}
                                        error={errors['pagamento.primeiro_vencimento']}
                                        required
                                    />

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Condicao de pagamento</label>
                                        <input
                                            type="text"
                                            value={data.payment.condicao_pagamento}
                                            onChange={(event) => updateNestedField('payment', 'condicao_pagamento', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['pagamento.condicao_pagamento']} />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="text-sm font-semibold text-slate-700">Chave / detalhe de cobranca</label>
                                        <input
                                            type="text"
                                            value={data.payment.chave_pagamento}
                                            onChange={(event) => updateNestedField('payment', 'chave_pagamento', event.target.value)}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['pagamento.chave_pagamento']} />
                                    </div>
                                </div>
                            </SectionCard>

                            <SectionCard
                                title="Observacoes operacionais"
                                description="Campos internos e observacoes fiscais complementares."
                            >
                                <div className="grid gap-4">
                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Observacao interna</label>
                                        <textarea
                                            value={data.observacoes.interna}
                                            onChange={(event) => updateNestedField('observacoes', 'interna', event.target.value)}
                                            rows={4}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['observacoes.interna']} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-semibold text-slate-700">Observacao fiscal</label>
                                        <textarea
                                            value={data.observacoes.fiscal}
                                            onChange={(event) => updateNestedField('observacoes', 'fiscal', event.target.value)}
                                            rows={4}
                                            className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                        />
                                        <FieldError message={errors['observacoes.fiscal']} />
                                    </div>
                                </div>
                            </SectionCard>
                        </div>

                        <aside className="space-y-6">
                            <section className="sticky top-40 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 className="text-lg font-semibold text-slate-900">Resumo financeiro</h3>
                                <div className="mt-5 space-y-4">
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-slate-500">Subtotal</span>
                                        <span className="font-semibold text-slate-900">{formatCurrency(computedTotals.subtotal)}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-slate-500">Desconto</span>
                                        <span className="font-semibold text-slate-900">{formatCurrency(computedTotals.discount)}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-slate-500">Parcela media</span>
                                        <span className="font-semibold text-slate-900">{formatCurrency(computedTotals.installmentValue)}</span>
                                    </div>
                                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                                        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Total</p>
                                        <p className="mt-2 text-3xl font-bold text-slate-900">{formatCurrency(computedTotals.total)}</p>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 className="text-lg font-semibold text-slate-900">Pendencias</h3>
                                <div className="mt-4 space-y-3">
                                    {localPendencias.length ? (
                                        localPendencias.map((item, index) => (
                                            <div
                                                key={`pending-${index}`}
                                                className={`rounded-2xl border px-4 py-3 text-sm ${
                                                    item.level === 'critical'
                                                        ? 'border-rose-200 bg-rose-50 text-rose-700'
                                                        : 'border-amber-200 bg-amber-50 text-amber-700'
                                                }`}
                                            >
                                                {item.message}
                                            </div>
                                        ))
                                    ) : (
                                        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                            Nenhuma pendencia aberta neste momento.
                                        </div>
                                    )}
                                </div>
                            </section>

                            <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                <h3 className="text-lg font-semibold text-slate-900">Auditoria</h3>
                                <div className="mt-4 space-y-3">
                                    {Array.isArray(launch?.history) && launch.history.length ? (
                                        launch.history.slice().reverse().map((event, index) => (
                                            <div key={`history-${index}`} className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                                <p className="font-semibold text-slate-900">{event.user_name ?? 'Sistema'}</p>
                                                <p className="text-slate-500">{event.at ?? '--'}</p>
                                                <p className="mt-1 text-slate-700">
                                                    {(event.action ?? 'update') === 'create' ? 'Criou' : 'Atualizou'} com status {event.status ?? '--'}.
                                                </p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-sm text-slate-500">O historico sera preenchido apos o primeiro salvamento.</p>
                                    )}
                                </div>
                            </section>
                        </aside>
                    </div>

                    <section className="rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-slate-900">Acoes do lancamento</h3>
                                <p className="text-sm text-slate-500">
                                    {isLocked
                                        ? 'Este lancamento esta bloqueado para edicao estrutural.'
                                        : 'Salve em etapas ate fechar todos os dados do seguro para a emissao.'}
                                </p>
                            </div>

                            {isLocked ? (
                                <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                                    Status atual: {launch?.status ?? data.status}
                                </div>
                            ) : (
                                <div className="flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        onClick={() => submitWithStatus('rascunho')}
                                        disabled={processing}
                                        className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        Salvar rascunho
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => submitWithStatus('revisao')}
                                        disabled={processing}
                                        className="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        Salvar em revisao
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => submitWithStatus('pronto_emissao')}
                                        disabled={processing}
                                        className="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        Marcar pronto para emissao
                                    </button>
                                    {isEditing ? (
                                        <button
                                            type="button"
                                            onClick={() => submitWithStatus('cancelada')}
                                            disabled={processing}
                                            className="rounded-2xl border border-slate-300 bg-slate-200 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-300 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            Cancelar lancamento
                                        </button>
                                    ) : null}
                                </div>
                            )}
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

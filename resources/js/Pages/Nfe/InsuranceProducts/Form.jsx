import AlertMessage from '@/Components/Alert/AlertMessage';
import Modal from '@/Components/Modal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const FieldError = ({ message }) =>
    message ? <p className="mt-1 text-sm text-rose-600">{message}</p> : null;

const booleanOptions = [
    { value: true, label: 'Sim' },
    { value: false, label: 'Nao' },
];

const fieldInfoContent = {
    codigoServicoNfse: {
        title: 'Codigo do servico da NFS-e',
        intro: 'E o campo que identifica qual servico foi prestado para fins de emissao da nota e tributacao do ISS.',
        codes: [
            '100102 = agenciamento, corretagem ou intermediacao de seguros',
            '180101 = regulacao de sinistros vinculados a contratos de seguros',
            '180102 = inspecao e avaliacao de riscos para cobertura de seguros',
            '180103 = prevencao e gerencia de riscos seguraveis',
            '990101 = servicos sem incidencia de ISSQN e ICMS',
        ],
    },
    tomadorNfse: {
        title: 'Tomador da NFS-e',
        intro: 'E quem recebe ou contrata o servico.',
        highlights: [
            'E quem esta pagando ou se beneficiando do servico.',
            'Pode ser pessoa fisica ou juridica.',
            'E quem aparece como cliente da nota.',
        ],
        contextTitle: 'No contexto de seguros',
        paragraphs: [
            'Depende do modelo operacional.',
            'Cenario 1 - mais comum: a corretora intermedia para a seguradora.',
            'Tomador = seguradora.',
        ],
        example: 'Corretora vende apolice da Porto Seguro, recebe comissao, emite NFS-e para a Porto e o tomador e a seguradora.',
    },
    prestadorNfse: {
        title: 'Prestador da NFS-e',
        intro: 'E quem executa o servico e emite a nota fiscal.',
        highlights: [
            'E a empresa que esta cobrando pelo servico.',
            'E quem tem inscricao municipal.',
            'E quem recolhe ou declara o ISS.',
        ],
        contextTitle: 'No contexto de seguros',
        paragraphs: [
            'Se houver corretagem ou intermediacao, o prestador normalmente e a corretora de seguros.',
            'Na pratica, costuma ser o CNPJ da corretora.',
        ],
        example: 'A corretora vende um seguro e recebe comissao, pode emitir NFS-e pela intermediacao e ela e o prestador.',
    },
};

function FieldLabelWithInfo({ label, infoKey, onOpenInfo }) {
    return (
        <span className="flex items-center gap-2 text-sm font-semibold text-slate-700">
            <span>{label}</span>
            <button
                type="button"
                onClick={() => onOpenInfo(infoKey)}
                className="inline-flex h-5 w-5 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-xs font-bold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100"
                aria-label={`Ver informacoes sobre ${label}`}
                title={`Ver informacoes sobre ${label}`}
            >
                <i className="bi bi-info-lg" aria-hidden="true" />
            </button>
        </span>
    );
}

export default function Form({
    auth,
    mode,
    units = [],
    insurers = [],
    selectedUnitId = null,
    statusOptions = [],
    product,
}) {
    const { flash } = usePage().props;
    const isEditing = mode === 'edit';
    const [activeInfoKey, setActiveInfoKey] = useState(null);
    const [isInsurerSearchOpen, setIsInsurerSearchOpen] = useState(false);
    const [insurerSearch, setInsurerSearch] = useState(product?.insurer_name ?? '');
    const { data, setData, post, put, processing, errors } = useForm({
        tb2_id: product?.unit_id ?? (selectedUnitId ? String(selectedUnitId) : ''),
        tb31_id: product?.insurer_id ?? '',
        tb30_codigo: product?.codigo ?? '',
        tb30_nome: product?.nome ?? '',
        tb30_ramo: product?.ramo ?? '',
        tb30_modalidade: product?.modalidade ?? '',
        tb30_tipo_contratacao: product?.tipo_contratacao ?? 'individual',
        tb30_periodicidade: product?.periodicidade ?? 'mensal',
        tb30_natureza_receita: product?.natureza_receita ?? 'premio de seguro',
        tb30_ramo_fiscal: product?.ramo_fiscal ?? 'seguro de danos',
        tb30_incide_iof: product?.incide_iof ?? true,
        tb30_aliquota_iof: product?.aliquota_iof ?? '7.38',
        tb30_permite_override_iof: product?.permite_override_iof ?? true,
        tb30_regra_base_iof: product?.regra_base_iof ?? 'premio',
        tb30_destacar_iof: product?.destacar_iof ?? true,
        tb30_ha_corretagem: product?.ha_corretagem ?? false,
        tb30_gera_nfse: product?.gera_nfse ?? true,
        tb30_item_lista_servico: product?.item_lista_servico ?? '10.01',
        tb30_codigo_servico_nfse: product?.codigo_servico_nfse ?? '',
        tb30_municipio_iss: product?.municipio_iss || 'Brasilia',
        tb30_uf_iss: product?.uf_iss || 'DF',
        tb30_codigo_ibge_iss: product?.codigo_ibge_iss || '5300108',
        tb30_aliquota_iss: product?.aliquota_iss ?? '0.00',
        tb30_prestador_nfse: product?.prestador_nfse ?? '',
        tb30_tomador_nfse: product?.tomador_nfse ?? '',
        tb30_cfop: product?.cfop ?? '',
        tb30_ncm: product?.ncm ?? '',
        tb30_unidade_padrao: product?.unidade_padrao ?? 'UN',
        tb30_premio_base: product?.premio_base ?? '0.00',
        tb30_comissao_percentual: product?.comissao_percentual ?? '0.00',
        tb30_regras: product?.regras ?? '',
        tb30_status: product?.status ?? '1',
    });

    const submit = () => {
        if (isEditing) {
            put(route('nfe.insurance-products.update', { insuranceProduct: product.id }), {
                preserveScroll: true,
            });
            return;
        }

        post(route('nfe.insurance-products.store'), {
            preserveScroll: true,
        });
    };

    const setBooleanField = (field, value) => {
        setData(field, value === '1');
    };

    const activeInfo = activeInfoKey ? fieldInfoContent[activeInfoKey] : null;
    const selectedInsurer = insurers.find((insurer) => String(insurer.id) === String(data.tb31_id)) ?? null;
    const filteredInsurers = insurers.filter((insurer) =>
        insurer.name.toLowerCase().includes(insurerSearch.trim().toLowerCase())
    );

    const handleInsurerSearchChange = (value) => {
        setInsurerSearch(value);
        setData('tb31_id', '');
        setIsInsurerSearchOpen(true);
    };

    const handleInsurerSelect = (insurer) => {
        setData('tb31_id', String(insurer.id));
        setInsurerSearch(insurer.name);
        setIsInsurerSearchOpen(false);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {isEditing ? 'Editar produto de seguro' : 'Novo produto de seguro'}
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Cadastro fiscal da NFe - Corretora de Seguros alinhado a IOF, corretagem e NFS-e.
                    </p>
                </div>
            )}
        >
            <Head title={isEditing ? 'Editar produto de seguro' : 'Novo produto de seguro'} />

            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <section className="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-900 p-6 text-white shadow-xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div className="space-y-3">
                                <span className="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-slate-100">
                                    NFe - Corretora de Seguros
                                </span>
                                <div className="space-y-2">
                                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                        {isEditing ? data.tb30_nome || 'Editar cadastro' : 'Novo cadastro fiscal de seguro'}
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-7 text-slate-200 sm:text-base">
                                        Cadastre o produto com foco em IOF e, quando houver intermediacao, com os dados de ISS/NFS-e na aplicacao NFe - Corretora de Seguros sem tratar CFOP e NCM como eixo principal.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route('nfe.insurance-products.index', selectedUnitId ? { unit_id: selectedUnitId } : {})}
                                className="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100"
                            >
                                Voltar para produtos
                            </Link>
                        </div>
                    </section>

                    <section className="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">Base do cadastro</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Identificacao comercial, escopo por matriz/unidade e parametros basicos do seguro.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label className="text-sm font-semibold text-slate-700">Escopo</label>
                                <select
                                    value={data.tb2_id}
                                    onChange={(event) => setData('tb2_id', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    <option value="">Matriz inteira</option>
                                    {units.map((unit) => (
                                        <option key={unit.id} value={unit.id}>
                                            {unit.name}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb2_id} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Codigo interno</label>
                                <input
                                    type="text"
                                    value={data.tb30_codigo}
                                    onChange={(event) => setData('tb30_codigo', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_codigo} />
                            </div>

                            <div className="xl:col-span-2">
                                <label className="text-sm font-semibold text-slate-700">Nome do produto</label>
                                <input
                                    type="text"
                                    value={data.tb30_nome}
                                    onChange={(event) => setData('tb30_nome', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_nome} />
                            </div>

                            <div className="md:col-span-2 xl:col-span-2">
                                <div className="flex items-center justify-between gap-3">
                                    <label className="text-sm font-semibold text-slate-700">Seguradora</label>
                                    <Link
                                        href={route('nfe.insurers.create')}
                                        className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100"
                                    >
                                        + Nova seguradora
                                    </Link>
                                </div>
                                <div
                                    className="relative mt-2"
                                    onBlur={(event) => {
                                        if (!event.currentTarget.contains(event.relatedTarget)) {
                                            setIsInsurerSearchOpen(false);
                                        }
                                    }}
                                >
                                    <input
                                        type="text"
                                        value={insurerSearch}
                                        onFocus={() => setIsInsurerSearchOpen(true)}
                                        onChange={(event) => handleInsurerSearchChange(event.target.value)}
                                        placeholder="Buscar seguradora por nome"
                                        className="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                    />
                                    {isInsurerSearchOpen ? (
                                        <div className="absolute z-20 mt-2 max-h-64 w-full overflow-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                                            {filteredInsurers.length ? (
                                                filteredInsurers.map((insurer) => (
                                                    <button
                                                        key={insurer.id}
                                                        type="button"
                                                        onClick={() => handleInsurerSelect(insurer)}
                                                        className={`flex w-full items-center justify-between rounded-2xl px-3 py-3 text-left text-sm transition ${
                                                            String(data.tb31_id) === String(insurer.id)
                                                                ? 'bg-blue-50 text-blue-900'
                                                                : 'text-slate-700 hover:bg-slate-50'
                                                        }`}
                                                    >
                                                        <span className="font-medium">{insurer.name}</span>
                                                        <span className="text-xs uppercase tracking-[0.14em] text-slate-400">
                                                            {insurer.uses_integration ? 'Integracao' : 'Manual'}
                                                        </span>
                                                    </button>
                                                ))
                                            ) : (
                                                <div className="rounded-2xl px-3 py-4 text-sm text-slate-500">
                                                    Nenhuma seguradora encontrada. Cadastre uma nova seguradora para continuar.
                                                </div>
                                            )}
                                        </div>
                                    ) : null}
                                </div>
                                {selectedInsurer ? (
                                    <p className="mt-2 text-xs text-slate-500">
                                        Seguradora selecionada: {selectedInsurer.name}
                                    </p>
                                ) : (
                                    <p className="mt-2 text-xs text-slate-500">
                                        Selecione uma seguradora ativa da matriz atual.
                                    </p>
                                )}
                                <FieldError message={errors.tb31_id} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Ramo</label>
                                <input
                                    type="text"
                                    value={data.tb30_ramo}
                                    onChange={(event) => setData('tb30_ramo', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_ramo} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Modalidade</label>
                                <input
                                    type="text"
                                    value={data.tb30_modalidade}
                                    onChange={(event) => setData('tb30_modalidade', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_modalidade} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Tipo de contratacao</label>
                                <select
                                    value={data.tb30_tipo_contratacao}
                                    onChange={(event) => setData('tb30_tipo_contratacao', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    <option value="individual">Individual</option>
                                    <option value="coletiva">Coletiva</option>
                                    <option value="mensal">Mensal</option>
                                    <option value="anual">Anual</option>
                                </select>
                                <FieldError message={errors.tb30_tipo_contratacao} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Periodicidade</label>
                                <select
                                    value={data.tb30_periodicidade}
                                    onChange={(event) => setData('tb30_periodicidade', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    <option value="mensal">Mensal</option>
                                    <option value="trimestral">Trimestral</option>
                                    <option value="semestral">Semestral</option>
                                    <option value="anual">Anual</option>
                                    <option value="unica">Parcela unica</option>
                                </select>
                                <FieldError message={errors.tb30_periodicidade} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Premio base</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.tb30_premio_base}
                                    onChange={(event) => setData('tb30_premio_base', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_premio_base} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Comissao padrao (%)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value={data.tb30_comissao_percentual}
                                    onChange={(event) => setData('tb30_comissao_percentual', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_comissao_percentual} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Status</label>
                                <select
                                    value={data.tb30_status}
                                    onChange={(event) => setData('tb30_status', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {statusOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb30_status} />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">Fiscal do produto</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Estrutura principal do seguro focada em natureza da receita e IOF.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div className="xl:col-span-2">
                                <label className="text-sm font-semibold text-slate-700">Natureza da receita</label>
                                <input
                                    type="text"
                                    value={data.tb30_natureza_receita}
                                    onChange={(event) => setData('tb30_natureza_receita', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_natureza_receita} />
                            </div>

                            <div className="xl:col-span-2">
                                <label className="text-sm font-semibold text-slate-700">Ramo fiscal</label>
                                <input
                                    type="text"
                                    value={data.tb30_ramo_fiscal}
                                    onChange={(event) => setData('tb30_ramo_fiscal', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_ramo_fiscal} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Incide IOF</label>
                                <select
                                    value={data.tb30_incide_iof ? '1' : '0'}
                                    onChange={(event) => setBooleanField('tb30_incide_iof', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {booleanOptions.map((option) => (
                                        <option key={option.label} value={option.value ? '1' : '0'}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb30_incide_iof} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Aliquota de IOF (%)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value={data.tb30_aliquota_iof}
                                    onChange={(event) => setData('tb30_aliquota_iof', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_aliquota_iof} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Permite override do IOF</label>
                                <select
                                    value={data.tb30_permite_override_iof ? '1' : '0'}
                                    onChange={(event) => setBooleanField('tb30_permite_override_iof', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {booleanOptions.map((option) => (
                                        <option key={option.label} value={option.value ? '1' : '0'}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb30_permite_override_iof} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Destacar IOF</label>
                                <select
                                    value={data.tb30_destacar_iof ? '1' : '0'}
                                    onChange={(event) => setBooleanField('tb30_destacar_iof', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {booleanOptions.map((option) => (
                                        <option key={option.label} value={option.value ? '1' : '0'}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb30_destacar_iof} />
                            </div>

                            <div className="md:col-span-2 xl:col-span-4">
                                <label className="text-sm font-semibold text-slate-700">Regra de base do IOF</label>
                                <input
                                    type="text"
                                    value={data.tb30_regra_base_iof}
                                    onChange={(event) => setData('tb30_regra_base_iof', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_regra_base_iof} />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                                <h3 className="text-lg font-semibold text-slate-900">Fiscal da intermediacao</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Use este bloco quando houver corretagem e faturamento de servico via ISS/NFS-e.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label className="text-sm font-semibold text-slate-700">Ha corretagem</label>
                                <select
                                    value={data.tb30_ha_corretagem ? '1' : '0'}
                                    onChange={(event) => setBooleanField('tb30_ha_corretagem', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {booleanOptions.map((option) => (
                                        <option key={option.label} value={option.value ? '1' : '0'}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb30_ha_corretagem} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Gera NFS-e</label>
                                <select
                                    value={data.tb30_gera_nfse ? '1' : '0'}
                                    onChange={(event) => setBooleanField('tb30_gera_nfse', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {booleanOptions.map((option) => (
                                        <option key={option.label} value={option.value ? '1' : '0'}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb30_gera_nfse} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Item da lista de servico</label>
                                <input
                                    type="text"
                                    value={data.tb30_item_lista_servico}
                                    onChange={(event) => setData('tb30_item_lista_servico', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_item_lista_servico} />
                            </div>

                            <div>
                                <label className="block">
                                    <FieldLabelWithInfo
                                        label="Codigo do servico NFS-e"
                                        infoKey="codigoServicoNfse"
                                        onOpenInfo={setActiveInfoKey}
                                    />
                                </label>
                                <input
                                    type="text"
                                    value={data.tb30_codigo_servico_nfse}
                                    onChange={(event) => setData('tb30_codigo_servico_nfse', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_codigo_servico_nfse} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Municipio de incidencia do ISS</label>
                                <input
                                    type="text"
                                    value={data.tb30_municipio_iss}
                                    onChange={(event) => setData('tb30_municipio_iss', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_municipio_iss} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">UF do ISS</label>
                                <input
                                    type="text"
                                    maxLength={2}
                                    value={data.tb30_uf_iss}
                                    onChange={(event) => setData('tb30_uf_iss', event.target.value.toUpperCase())}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm uppercase text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_uf_iss} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Codigo IBGE do municipio</label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={7}
                                    value={data.tb30_codigo_ibge_iss}
                                    onChange={(event) => setData('tb30_codigo_ibge_iss', event.target.value.replace(/\D+/g, ''))}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_codigo_ibge_iss} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Aliquota de ISS (%)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value={data.tb30_aliquota_iss}
                                    onChange={(event) => setData('tb30_aliquota_iss', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_aliquota_iss} />
                            </div>

                            <div className="xl:col-span-2">
                                <label className="block">
                                    <FieldLabelWithInfo
                                        label="Prestador da NFS-e"
                                        infoKey="prestadorNfse"
                                        onOpenInfo={setActiveInfoKey}
                                    />
                                </label>
                                <input
                                    type="text"
                                    value={data.tb30_prestador_nfse}
                                    onChange={(event) => setData('tb30_prestador_nfse', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_prestador_nfse} />
                            </div>

                            <div className="xl:col-span-2">
                                <label className="block">
                                    <FieldLabelWithInfo
                                        label="Tomador da NFS-e (opcional)"
                                        infoKey="tomadorNfse"
                                        onOpenInfo={setActiveInfoKey}
                                    />
                                </label>
                                <input
                                    type="text"
                                    value={data.tb30_tomador_nfse}
                                    onChange={(event) => setData('tb30_tomador_nfse', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <p className="mt-2 text-xs text-slate-500">
                                    Use apenas se quiser deixar um tomador padrao de referencia para o produto. O tomador final pode mudar em cada venda.
                                </p>
                                <FieldError message={errors.tb30_tomador_nfse} />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">Campos de apoio</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Mantenha aqui apenas os campos complementares que podem ser exigidos em cenarios especificos.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label className="text-sm font-semibold text-slate-700">CFOP opcional</label>
                                <input
                                    type="text"
                                    value={data.tb30_cfop}
                                    onChange={(event) => setData('tb30_cfop', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_cfop} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">NCM opcional</label>
                                <input
                                    type="text"
                                    value={data.tb30_ncm}
                                    onChange={(event) => setData('tb30_ncm', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_ncm} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Unidade padrao</label>
                                <input
                                    type="text"
                                    value={data.tb30_unidade_padrao}
                                    onChange={(event) => setData('tb30_unidade_padrao', event.target.value.toUpperCase())}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm uppercase text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_unidade_padrao} />
                            </div>

                            <div className="md:col-span-2 xl:col-span-4">
                                <label className="text-sm font-semibold text-slate-700">Regras e observacoes operacionais</label>
                                <textarea
                                    value={data.tb30_regras}
                                    onChange={(event) => setData('tb30_regras', event.target.value)}
                                    rows={5}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_regras} />
                            </div>
                        </div>
                    </section>

                    <section className="rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-slate-900">Salvar cadastro</h3>
                                <p className="text-sm text-slate-500">
                                    Este produto alimentara os lancamentos da NFe - Corretora de Seguros com defaults fiscais de IOF e intermediacao.
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    onClick={submit}
                                    disabled={processing}
                                    className="rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {isEditing ? 'Salvar alteracoes' : 'Cadastrar produto'}
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <Modal show={Boolean(activeInfo)} onClose={() => setActiveInfoKey(null)} maxWidth="2xl" tone="light">
                <div className="border-b border-slate-200 px-6 py-4">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">
                                {activeInfo?.title ?? 'Informacoes do campo'}
                            </h3>
                            {activeInfo?.intro ? (
                                <p className="mt-1 text-sm text-slate-600">
                                    {activeInfo.intro}
                                </p>
                            ) : null}
                        </div>

                        <button
                            type="button"
                            onClick={() => setActiveInfoKey(null)}
                            className="rounded-full border border-slate-200 px-3 py-1 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                        >
                            Fechar
                        </button>
                    </div>
                </div>

                <div className="space-y-5 px-6 py-5 text-sm leading-7 text-slate-700">
                    {activeInfo?.codes?.length ? (
                        <div>
                            <h4 className="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Codigos sugeridos
                            </h4>
                            <div className="mt-3 space-y-2">
                                {activeInfo.codes.map((item) => (
                                    <p key={item} className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        {item}
                                    </p>
                                ))}
                            </div>
                        </div>
                    ) : null}

                    {activeInfo?.highlights?.length ? (
                        <div>
                            <h4 className="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Em termos praticos
                            </h4>
                            <div className="mt-3 space-y-2">
                                {activeInfo.highlights.map((item) => (
                                    <p key={item} className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        {item}
                                    </p>
                                ))}
                            </div>
                        </div>
                    ) : null}

                    {activeInfo?.contextTitle ? (
                        <div>
                            <h4 className="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">
                                {activeInfo.contextTitle}
                            </h4>
                            <div className="mt-3 space-y-2">
                                {(activeInfo.paragraphs ?? []).map((item) => (
                                    <p key={item}>{item}</p>
                                ))}
                            </div>
                        </div>
                    ) : null}

                    {activeInfo?.example ? (
                        <div className="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-4">
                            <h4 className="text-sm font-semibold uppercase tracking-[0.16em] text-blue-700">
                                Exemplo
                            </h4>
                            <p className="mt-2 text-sm leading-7 text-blue-900">
                                {activeInfo.example}
                            </p>
                        </div>
                    ) : null}
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

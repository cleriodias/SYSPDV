import AlertMessage from '@/Components/Alert/AlertMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

const formatCounter = (value) => Number(value ?? 0).toLocaleString('pt-BR');

const ActionCard = ({
    title,
    description,
    href = null,
    accentClassName,
    counter,
    counterLabel,
    badge,
    icon,
    disabled = false,
}) => {
    const content = (
        <div className={`group flex h-full flex-col justify-between rounded-3xl border bg-white p-6 shadow-sm transition ${accentClassName}`}>
            <div className="space-y-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">{badge}</p>
                        <h3 className="mt-2 text-2xl font-semibold text-gray-900">{title}</h3>
                    </div>
                    <span className="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-2xl text-gray-700 shadow-sm">
                        <i className={icon} aria-hidden="true"></i>
                    </span>
                </div>
                <p className="text-sm leading-6 text-gray-600">{description}</p>
            </div>

            <div className="mt-8 flex items-end justify-between gap-4">
                <div>
                    <p className="text-3xl font-bold text-gray-900">{formatCounter(counter)}</p>
                    <p className="text-sm text-gray-500">{counterLabel}</p>
                </div>
                <span className="text-sm font-semibold text-gray-700">
                    {disabled ? 'Sem permissao' : 'Abrir'}
                </span>
            </div>
        </div>
    );

    if (disabled || !href) {
        return content;
    }

    return (
        <Link href={href} className="block h-full">
            {content}
        </Link>
    );
};

export default function Dashboard({
    auth,
    units = [],
    selectedUnitId = null,
    unit = null,
    canAccessFiscalSettings = false,
    canAccessInvoiceMonitor = false,
    configurationReady = false,
    insuranceProductCount = 0,
    insurerCount = 0,
    invoiceSummary = {
        issued: 0,
        signed: 0,
        errors: 0,
    },
    launchSummary = {
        total: 0,
        draft: 0,
        ready: 0,
    },
}) {
    const { flash } = usePage().props;

    const handleSelectUnit = (unitId) => {
        router.get(route('nfe'), { unit_id: unitId }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">Dashboard NFe</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Entrada da aplicacao NFe com configuracao fiscal, catalogo de seguros e lancamentos.
                    </p>
                </div>
            )}
        >
            <Head title="Dashboard NFe" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <section className="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-900 p-6 text-white shadow-xl">
                        <div className="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
                            <div className="space-y-4">
                                <span className="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-slate-100">
                                    Aplicacao NFe
                                </span>
                                <div className="space-y-2">
                                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                        Operacao fiscal organizada para produtos de seguro.
                                    </h1>
                                    <p className="max-w-2xl text-sm leading-7 text-slate-200 sm:text-base">
                                        Use este painel para entrar direto na configuracao fiscal da unidade selecionada
                                        e acessar o catalogo de seguros e os lancamentos da matriz ativa.
                                    </p>
                                </div>
                            </div>

                            <div className="rounded-[1.75rem] border border-white/15 bg-white/10 p-5 backdrop-blur">
                                <p className="text-xs font-semibold uppercase tracking-[0.25em] text-slate-200">
                                    Unidade ativa
                                </p>
                                <h2 className="mt-3 text-2xl font-semibold">
                                    {unit?.name ?? 'Selecione uma unidade'}
                                </h2>
                                <div className="mt-4 space-y-2 text-sm text-slate-200">
                                    <p>CNPJ: {unit?.cnpj ?? '--'}</p>
                                    <p>Endereco: {unit?.endereco ?? '--'}</p>
                                    <p>Configuracao fiscal: {configurationReady ? 'Pronta para emissao' : 'Pendente de configuracao'}</p>
                                </div>
                                {canAccessInvoiceMonitor && selectedUnitId ? (
                                    <Link
                                        href={route('settings.nfe', { unit_id: selectedUnitId })}
                                        className="mt-5 inline-flex items-center rounded-full border border-white/25 bg-white/15 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/25"
                                    >
                                        Abrir monitor NFe
                                    </Link>
                                ) : null}
                            </div>
                        </div>
                    </section>

                    <section className="rounded-3xl bg-white p-6 shadow dark:bg-gray-800">
                        <div className="flex flex-col gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Escolha a unidade</h3>
                                <p className="text-sm text-gray-500 dark:text-gray-300">
                                    A configuracao fiscal e o acompanhamento das notas respeitam a unidade selecionada.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                {units.map((store) => {
                                    const isActive = Number(selectedUnitId) === Number(store.id);

                                    return (
                                        <button
                                            key={store.id}
                                            type="button"
                                            onClick={() => handleSelectUnit(store.id)}
                                            className={`rounded-full border px-5 py-3 text-sm font-semibold transition ${
                                                isActive
                                                    ? 'border-blue-600 bg-blue-500 text-white shadow-sm'
                                                    : 'border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:text-blue-700'
                                            }`}
                                        >
                                            {store.name}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-5 xl:grid-cols-4">
                        <ActionCard
                            title="Configuracao fiscal"
                            description="Cadastre certificado, dados fiscais, ambiente e parametros da unidade selecionada."
                            href={canAccessFiscalSettings && selectedUnitId ? route('settings.fiscal', { unit_id: selectedUnitId }) : null}
                            accentClassName={configurationReady ? 'border-emerald-200 hover:border-emerald-300' : 'border-amber-200 hover:border-amber-300'}
                            counter={configurationReady ? 1 : 0}
                            counterLabel={configurationReady ? 'unidade pronta para emissao' : 'unidade pendente'}
                            badge={configurationReady ? 'Configurada' : 'Pendente'}
                            icon="bi bi-gear"
                            disabled={!canAccessFiscalSettings}
                        />

                        <ActionCard
                            title="Produtos de seguro"
                            description="Cadastre seguradora, ramo, premio base, comissao e regras operacionais da carteira."
                            href={route('nfe.insurance-products.index', selectedUnitId ? { unit_id: selectedUnitId } : {})}
                            accentClassName="border-blue-200 hover:border-blue-300"
                            counter={insuranceProductCount}
                            counterLabel="produtos ativos no catalogo"
                            badge="Seguros"
                            icon="bi bi-box-seam"
                        />

                        <ActionCard
                            title="Seguradoras da carteira"
                            description="Visualize rapidamente quantas seguradoras ativas estao configuradas para o contexto selecionado."
                            href={route('nfe.insurance-products.index', selectedUnitId ? { unit_id: selectedUnitId } : {})}
                            accentClassName="border-green-200 hover:border-green-300"
                            counter={insurerCount}
                            counterLabel="seguradoras com produtos ativos"
                            badge="Carteira"
                            icon="bi bi-shield-check"
                        />

                        <ActionCard
                            title="Lancamentos NFe"
                            description="Monte os dados da nota por unidade com pendencias, auditoria e fechamento operacional."
                            href={selectedUnitId ? route('nfe.launches.index', { unit_id: selectedUnitId }) : null}
                            accentClassName="border-sky-200 hover:border-sky-300"
                            counter={launchSummary?.total}
                            counterLabel={`${formatCounter(launchSummary?.ready)} prontos para emissao`}
                            badge="Lancamentos"
                            icon="bi bi-journal-check"
                            disabled={!selectedUnitId}
                        />
                    </section>

                    <section className="grid gap-5 md:grid-cols-3">
                        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Emitidas</p>
                            <p className="mt-3 text-3xl font-bold text-slate-900">{formatCounter(invoiceSummary?.issued)}</p>
                            <p className="mt-2 text-sm text-slate-500">Notas emitidas da unidade selecionada.</p>
                        </div>
                        <div className="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Assinadas</p>
                            <p className="mt-3 text-3xl font-bold text-slate-900">{formatCounter(invoiceSummary?.signed)}</p>
                            <p className="mt-2 text-sm text-slate-500">Notas prontas para transmissao ou conferencia.</p>
                        </div>
                        <div className="rounded-3xl border border-rose-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-rose-700">Com erro</p>
                            <p className="mt-3 text-3xl font-bold text-slate-900">{formatCounter(invoiceSummary?.errors)}</p>
                            <p className="mt-2 text-sm text-slate-500">Notas que precisam de ajuste antes de concluir a emissao.</p>
                        </div>
                    </section>

                    <section className="grid gap-5 md:grid-cols-2">
                        <div className="rounded-3xl border border-sky-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Lancamentos abertos</p>
                            <p className="mt-3 text-3xl font-bold text-slate-900">{formatCounter(launchSummary?.draft)}</p>
                            <p className="mt-2 text-sm text-slate-500">Rascunhos ou revisoes da unidade selecionada.</p>
                        </div>
                        <div className="rounded-3xl border border-emerald-200 bg-white p-5 shadow-sm">
                            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Prontos para emissao</p>
                            <p className="mt-3 text-3xl font-bold text-slate-900">{formatCounter(launchSummary?.ready)}</p>
                            <p className="mt-2 text-sm text-slate-500">Lancamentos com fechamento operacional concluido.</p>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

import AlertMessage from '@/Components/Alert/AlertMessage';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const STATUS_STYLES = {
    default: 'border-slate-200 bg-slate-50 text-slate-700',
    primary: 'border-blue-200 bg-blue-50 text-blue-700',
    secondary: 'border-slate-200 bg-slate-100 text-slate-700',
    info: 'border-cyan-200 bg-cyan-50 text-cyan-700',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    warning: 'border-amber-200 bg-amber-50 text-amber-700',
    error: 'border-rose-200 bg-rose-50 text-rose-700',
    dark: 'border-slate-300 bg-slate-200 text-slate-800',
    light: 'border-slate-200 bg-white text-slate-700',
};

const metricClassNames = [
    'border-blue-200 bg-blue-50 text-blue-900',
    'border-slate-200 bg-slate-50 text-slate-900',
    'border-cyan-200 bg-cyan-50 text-cyan-900',
    'border-emerald-200 bg-emerald-50 text-emerald-900',
    'border-slate-300 bg-slate-200 text-slate-900',
];

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

const resolveBadgeClassName = (color) =>
    `inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${
        STATUS_STYLES[color] ?? STATUS_STYLES.default
    }`;

export default function Index({
    auth,
    units = [],
    selectedUnitId = null,
    launches,
    filters,
    summary,
    statusOptions = [],
}) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');

    const applyFilters = (overrides = {}) => {
        router.get(route('nfe.launches.index'), {
            unit_id: overrides.unitId ?? selectedUnitId ?? '',
            status: overrides.status ?? status,
            search: overrides.search ?? search,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const metrics = [
        { label: 'Total', value: summary?.total ?? 0 },
        { label: 'Rascunho', value: summary?.rascunho ?? 0 },
        { label: 'Em revisao', value: summary?.revisao ?? 0 },
        { label: 'Pronto emissao', value: summary?.pronto_emissao ?? 0 },
        { label: 'Cancelados', value: summary?.cancelada ?? 0 },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">Lancamentos NFe</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Estruture os dados operacionais antes da geracao da nota fiscal.
                    </p>
                </div>
            )}
        >
            <Head title="Lancamentos NFe" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <section className="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-900 p-6 text-white shadow-xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div className="space-y-3">
                                <span className="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-slate-100">
                                    Aplicacao NFe
                                </span>
                                <div className="space-y-2">
                                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                        Tela de lancamentos para geracao de notas.
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-7 text-slate-200 sm:text-base">
                                        Use esta fila para organizar destinatario, itens, pagamento, pendencias e auditoria
                                        antes da emissao fiscal.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route('nfe.launches.create', selectedUnitId ? { unit_id: selectedUnitId } : {})}
                                className="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100"
                            >
                                Novo lancamento
                            </Link>
                        </div>
                    </section>

                    <section className="grid gap-4 md:grid-cols-5">
                        {metrics.map((metric, index) => (
                            <div
                                key={metric.label}
                                className={`rounded-3xl border p-5 shadow-sm ${metricClassNames[index] ?? metricClassNames[0]}`}
                            >
                                <p className="text-xs font-semibold uppercase tracking-[0.2em] opacity-80">{metric.label}</p>
                                <p className="mt-3 text-3xl font-bold">{Number(metric.value ?? 0).toLocaleString('pt-BR')}</p>
                            </div>
                        ))}
                    </section>

                    <section className="rounded-3xl bg-white p-6 shadow">
                        <div className="grid gap-4 xl:grid-cols-[1.3fr_1fr_1fr_auto]">
                            <div>
                                <label className="text-sm font-semibold text-slate-700">Unidade</label>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {units.map((unit) => {
                                        const isActive = Number(selectedUnitId) === Number(unit.id);

                                        return (
                                            <button
                                                key={unit.id}
                                                type="button"
                                                onClick={() => applyFilters({ unitId: unit.id })}
                                                className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
                                                    isActive
                                                        ? 'border-blue-600 bg-blue-600 text-white'
                                                        : 'border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:text-blue-700'
                                                }`}
                                            >
                                                {unit.name}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div>
                                <label htmlFor="status-filter" className="text-sm font-semibold text-slate-700">
                                    Status
                                </label>
                                <select
                                    id="status-filter"
                                    value={status}
                                    onChange={(event) => {
                                        const nextStatus = event.target.value;
                                        setStatus(nextStatus);
                                        applyFilters({ status: nextStatus });
                                    }}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {statusOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label htmlFor="search-filter" className="text-sm font-semibold text-slate-700">
                                    Buscar
                                </label>
                                <input
                                    id="search-filter"
                                    type="text"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            event.preventDefault();
                                            applyFilters({ search });
                                        }
                                    }}
                                    placeholder="Numero, nome ou documento"
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                            </div>

                            <div className="flex items-end">
                                <button
                                    type="button"
                                    onClick={() => applyFilters({ search })}
                                    className="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                                >
                                    Filtrar
                                </button>
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-3xl bg-white shadow">
                        <div className="border-b border-slate-100 px-6 py-4">
                            <h3 className="text-lg font-semibold text-slate-900">Fila de lancamentos</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Cada registro fica isolado por unidade e pronto para evoluir ao fluxo fiscal da aplicacao NFe.
                            </p>
                        </div>

                        {!launches?.data?.length ? (
                            <div className="px-6 py-16 text-center text-sm text-slate-500">
                                Nenhum lancamento encontrado para os filtros selecionados.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Numero</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Destinatario</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Operacao</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Data</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Total</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Atualizado</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Acao</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {launches.data.map((launch) => (
                                            <tr key={launch.id}>
                                                <td className="px-4 py-4 font-semibold text-slate-900">{launch.number}</td>
                                                <td className="px-4 py-4">
                                                    <span className={resolveBadgeClassName(launch.status_color)}>
                                                        {launch.status_label}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div className="font-medium text-slate-900">{launch.recipient_name}</div>
                                                    <div className="text-xs text-slate-500">{launch.recipient_document}</div>
                                                </td>
                                                <td className="px-4 py-4 text-slate-700">{launch.operation_type}</td>
                                                <td className="px-4 py-4 text-slate-700">{launch.launch_date ?? '--'}</td>
                                                <td className="px-4 py-4 font-semibold text-slate-900">{formatCurrency(launch.total)}</td>
                                                <td className="px-4 py-4 text-slate-700">{launch.updated_at ?? '--'}</td>
                                                <td className="px-4 py-4">
                                                    <Link
                                                        href={launch.edit_url}
                                                        className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100"
                                                    >
                                                        Abrir
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>

                                <Pagination
                                    links={launches.links}
                                    currentPage={launches.current_page}
                                />
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

import AlertMessage from '@/Components/Alert/AlertMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const metricClassNames = [
    'border-blue-200 bg-blue-50 text-blue-900',
    'border-emerald-200 bg-emerald-50 text-emerald-900',
    'border-slate-300 bg-slate-200 text-slate-900',
    'border-cyan-200 bg-cyan-50 text-cyan-900',
];

export default function Index({
    auth,
    insurers = [],
    filters = {},
    summary = {},
    statusOptions = [],
}) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');

    const applyFilters = (overrides = {}) => {
        router.get(route('nfe.insurers.index'), {
            search: overrides.search ?? search,
            status: overrides.status ?? status,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const metrics = [
        { label: 'Total', value: summary?.total ?? 0 },
        { label: 'Ativas', value: summary?.active ?? 0 },
        { label: 'Inativas', value: summary?.inactive ?? 0 },
        { label: 'Com integracao', value: summary?.integrated ?? 0 },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">Seguradoras</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Cadastro auxiliar mestre da NFe - Corretora de Seguros.
                    </p>
                </div>
            )}
        >
            <Head title="Seguradoras" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <section className="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-900 p-6 text-white shadow-xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div className="space-y-3">
                                <span className="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-slate-100">
                                    NFe - Corretora de Seguros
                                </span>
                                <div className="space-y-2">
                                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                        Cadastro mestre de seguradoras.
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-7 text-slate-200 sm:text-base">
                                        Padronize seguradoras por matriz para usar combo com busca no cadastro do produto, evitar duplicidade e suportar integracoes.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route('nfe.insurers.create')}
                                className="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100"
                            >
                                Nova seguradora
                            </Link>
                        </div>
                    </section>

                    <section className="grid gap-4 md:grid-cols-4">
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
                        <div className="grid gap-4 xl:grid-cols-[1fr_280px_140px]">
                            <div>
                                <label className="text-sm font-semibold text-slate-700">Buscar</label>
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            event.preventDefault();
                                            applyFilters({ search });
                                        }
                                    }}
                                    placeholder="Nome fantasia, razao social, CNPJ ou codigo"
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Status</label>
                                <select
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
                            <h3 className="text-lg font-semibold text-slate-900">Seguradoras da matriz</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Somente seguradoras ativas podem ser selecionadas no produto de seguro.
                            </p>
                        </div>

                        {!insurers.length ? (
                            <div className="px-6 py-16 text-center text-sm text-slate-500">
                                Nenhuma seguradora cadastrada para esta matriz.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Seguradora</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">CNPJ</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Codigo</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Integracao</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Acao</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {insurers.map((insurer) => (
                                            <tr key={insurer.id}>
                                                <td className="px-4 py-4">
                                                    <div className="font-medium text-slate-900">{insurer.fantasy_name}</div>
                                                    <div className="text-xs text-slate-500">{insurer.company_name || 'Razao social nao informada'}</div>
                                                </td>
                                                <td className="px-4 py-4 text-slate-700">{insurer.cnpj || '--'}</td>
                                                <td className="px-4 py-4 text-slate-700">{insurer.susep_code || '--'}</td>
                                                <td className="px-4 py-4">
                                                    <div className="text-slate-700">{insurer.uses_integration ? 'Sim' : 'Nao'}</div>
                                                    <div className="text-xs text-slate-500">{insurer.external_integration_code || '--'}</div>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <span className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${
                                                        Number(insurer.status) === 1
                                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                            : 'border-slate-300 bg-slate-200 text-slate-800'
                                                    }`}>
                                                        {insurer.status_label}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <Link
                                                        href={insurer.edit_url}
                                                        className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100"
                                                    >
                                                        Abrir
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

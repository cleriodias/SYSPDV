import AlertMessage from '@/Components/Alert/AlertMessage';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

const metricClassNames = [
    'border-blue-200 bg-blue-50 text-blue-900',
    'border-emerald-200 bg-emerald-50 text-emerald-900',
    'border-slate-300 bg-slate-200 text-slate-900',
    'border-cyan-200 bg-cyan-50 text-cyan-900',
];

export default function Index({
    auth,
    units = [],
    selectedUnitId = null,
    products,
    filters,
    summary,
    statusOptions = [],
}) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');

    const applyFilters = (overrides = {}) => {
        router.get(route('nfe.insurance-products.index'), {
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
        { label: 'Ativos', value: summary?.active ?? 0 },
        { label: 'Inativos', value: summary?.inactive ?? 0 },
        { label: 'Seguradoras', value: summary?.insurers ?? 0 },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">Produtos de Seguro</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Catalogo proprio da aplicacao NFe voltado para seguros.
                    </p>
                </div>
            )}
        >
            <Head title="Produtos de Seguro" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <section className="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-900 p-6 text-white shadow-xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div className="space-y-3">
                                <span className="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-slate-100">
                                    Aplicacao NFe
                                </span>
                                <div className="space-y-2">
                                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                        Cadastro de produtos focado na area de seguros.
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-7 text-slate-200 sm:text-base">
                                        Cadastre seguradora, ramo, modalidade, contratacao, premio base e regras operacionais
                                        sem usar o catalogo generico da padaria.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route('nfe.insurance-products.create', selectedUnitId ? { unit_id: selectedUnitId } : {})}
                                className="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100"
                            >
                                Novo produto de seguro
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
                        <div className="grid gap-4 xl:grid-cols-[1.3fr_1fr_1fr_auto]">
                            <div>
                                <label className="text-sm font-semibold text-slate-700">Unidade</label>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() => applyFilters({ unitId: '' })}
                                        className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
                                            !selectedUnitId
                                                ? 'border-blue-600 bg-blue-600 text-white'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:text-blue-700'
                                        }`}
                                    >
                                        Matriz
                                    </button>
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
                                    placeholder="Codigo, nome, seguradora ou ramo"
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
                            <h3 className="text-lg font-semibold text-slate-900">Catalogo de seguros</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Produtos compartilhados pela matriz ou exclusivos por unidade, sempre dentro do escopo correto.
                            </p>
                        </div>

                        {!products?.data?.length ? (
                            <div className="px-6 py-16 text-center text-sm text-slate-500">
                                Nenhum produto de seguro encontrado para os filtros selecionados.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Codigo</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Produto</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Seguradora</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Ramo</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Escopo</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Premio base</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Comissao</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                            <th className="px-4 py-3 text-left font-semibold text-slate-600">Acao</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {products.data.map((product) => (
                                            <tr key={product.id}>
                                                <td className="px-4 py-4 font-semibold text-slate-900">{product.code}</td>
                                                <td className="px-4 py-4">
                                                    <div className="font-medium text-slate-900">{product.name}</div>
                                                    <div className="text-xs text-slate-500">
                                                        {product.modality || product.contract_type} | {product.periodicity}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4 text-slate-700">{product.insurer}</td>
                                                <td className="px-4 py-4 text-slate-700">{product.branch}</td>
                                                <td className="px-4 py-4 text-slate-700">{product.unit_name}</td>
                                                <td className="px-4 py-4 font-semibold text-slate-900">{formatCurrency(product.premium)}</td>
                                                <td className="px-4 py-4 text-slate-700">{Number(product.commission ?? 0).toLocaleString('pt-BR')}%</td>
                                                <td className="px-4 py-4">
                                                    <span className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${
                                                        Number(product.status) === 1
                                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                            : 'border-slate-300 bg-slate-200 text-slate-800'
                                                    }`}>
                                                        {product.status_label}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <Link
                                                        href={product.edit_url}
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
                                    links={products.links}
                                    currentPage={products.current_page}
                                />
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

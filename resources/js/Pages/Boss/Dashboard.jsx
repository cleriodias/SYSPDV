import AlertMessage from '@/Components/Alert/AlertMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

export default function BossDashboard({ planSettings, summary, matrizes }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        matrix_monthly_price: String(planSettings?.matrix_monthly_price ?? 250),
        branch_monthly_price: String(planSettings?.branch_monthly_price ?? 180),
        hosting_monthly_price: String(planSettings?.hosting_monthly_price ?? 70),
        purchase_matrix_price: String(planSettings?.purchase_matrix_price ?? 10000),
        purchase_branch_price: String(planSettings?.purchase_branch_price ?? 5000),
        purchase_installments: String(planSettings?.purchase_installments ?? 15),
    });

    const submit = (event) => {
        event.preventDefault();
        put(route('settings.billing-plans.update'));
    };

    const toggleMatrixPayment = (matrixId) => {
        router.put(route('settings.billing-status.matrices.payment', { matriz: matrixId }), {}, {
            preserveScroll: true,
        });
    };

    const toggleUnitPayment = (unitId) => {
        router.put(route('settings.billing-status.units.payment', { unit: unitId }), {}, {
            preserveScroll: true,
        });
    };

    const toggleUnitLogin = (unitId) => {
        router.put(route('settings.billing-status.units.login', { unit: unitId }), {}, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Dashboard BOSS
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Controle comercial e faturamento mensal das matrizes e filiais.
                    </p>
                </div>
            }
        >
            <Head title="Dashboard BOSS" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div className="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
                            <p className="text-xs font-bold uppercase tracking-[0.24em] text-emerald-700">Matrizes</p>
                            <p className="mt-3 text-3xl font-extrabold text-slate-900">{summary?.matrices_count ?? 0}</p>
                            <p className="mt-2 text-sm text-slate-500">
                                {formatCurrency(summary?.matrix_monthly_total)} por mes
                            </p>
                        </div>

                        <div className="rounded-3xl border border-orange-100 bg-white p-6 shadow-sm">
                            <p className="text-xs font-bold uppercase tracking-[0.24em] text-orange-700">Filiais</p>
                            <p className="mt-3 text-3xl font-extrabold text-slate-900">{summary?.branches_count ?? 0}</p>
                            <p className="mt-2 text-sm text-slate-500">
                                {formatCurrency(summary?.branch_monthly_total)} por mes
                            </p>
                        </div>

                        <div className="rounded-3xl border border-sky-100 bg-white p-6 shadow-sm">
                            <p className="text-xs font-bold uppercase tracking-[0.24em] text-sky-700">Faturamento</p>
                            <p className="mt-3 text-3xl font-extrabold text-slate-900">
                                {formatCurrency(summary?.grand_monthly_total)}
                            </p>
                            <p className="mt-2 text-sm text-slate-500">receita mensal recorrente</p>
                        </div>

                        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p className="text-xs font-bold uppercase tracking-[0.24em] text-slate-700">Plano matriz atual</p>
                            <p className="mt-3 text-3xl font-extrabold text-slate-900">
                                {formatCurrency(planSettings?.matrix_monthly_price)}
                            </p>
                            <p className="mt-2 text-sm text-slate-500">aplicado em novas matrizes</p>
                        </div>

                        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p className="text-xs font-bold uppercase tracking-[0.24em] text-slate-700">Plano filial atual</p>
                            <p className="mt-3 text-3xl font-extrabold text-slate-900">
                                {formatCurrency(planSettings?.branch_monthly_price)}
                            </p>
                            <p className="mt-2 text-sm text-slate-500">aplicado em novas filiais</p>
                        </div>
                    </div>

                    <form onSubmit={submit} className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">Planos atuais do sistema</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Os novos valores entram apenas para futuras contratacoes.
                            </p>
                        </div>

                        <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1fr_0.8fr_0.9fr_auto] xl:items-end">
                            <div>
                                <label className="text-sm font-medium text-slate-700">Mensalidade Matriz</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.matrix_monthly_price}
                                    onChange={(event) => setData('matrix_monthly_price', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                />
                                {errors.matrix_monthly_price && <p className="mt-1 text-xs text-red-600">{errors.matrix_monthly_price}</p>}
                            </div>

                            <div>
                                <label className="text-sm font-medium text-slate-700">Mensalidade Filial</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.branch_monthly_price}
                                    onChange={(event) => setData('branch_monthly_price', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                />
                                {errors.branch_monthly_price && <p className="mt-1 text-xs text-red-600">{errors.branch_monthly_price}</p>}
                            </div>

                            <div>
                                <label className="text-sm font-medium text-slate-700">Compra Matriz</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.purchase_matrix_price}
                                    onChange={(event) => setData('purchase_matrix_price', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                />
                                {errors.purchase_matrix_price && <p className="mt-1 text-xs text-red-600">{errors.purchase_matrix_price}</p>}
                            </div>

                            <div>
                                <label className="text-sm font-medium text-slate-700">Compra Filial</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.purchase_branch_price}
                                    onChange={(event) => setData('purchase_branch_price', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                />
                                {errors.purchase_branch_price && <p className="mt-1 text-xs text-red-600">{errors.purchase_branch_price}</p>}
                            </div>

                            <div>
                                <label className="text-sm font-medium text-slate-700">Parcelamento</label>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    value={data.purchase_installments}
                                    onChange={(event) => setData('purchase_installments', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                />
                                {errors.purchase_installments && <p className="mt-1 text-xs text-red-600">{errors.purchase_installments}</p>}
                            </div>

                            <div>
                                <label className="text-sm font-medium text-slate-700">Hospedagem mensal</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.hosting_monthly_price}
                                    onChange={(event) => setData('hosting_monthly_price', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                />
                                {errors.hosting_monthly_price && <p className="mt-1 text-xs text-red-600">{errors.hosting_monthly_price}</p>}
                            </div>

                            <div className="flex self-end xl:justify-start">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-2xl bg-emerald-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-70 xl:mt-0 xl:w-auto"
                                >
                                    {processing ? 'Salvando...' : 'Salvar planos'}
                                </button>
                            </div>
                        </div>
                    </form>

                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 className="text-lg font-semibold text-slate-900">Faturamento por Matriz</h3>
                        <p className="mt-1 text-sm text-slate-500">
                            Cada valor contratado fica congelado no cadastro da matriz e das filiais.
                        </p>

                        <div className="mt-5 space-y-3">
                            {matrizes?.length ? matrizes.map((matriz) => (
                                <article key={matriz.id} className="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                    <div className="grid gap-3 xl:grid-cols-[1.5fr_0.85fr_0.7fr_0.9fr_1fr] xl:items-center">
                                        <div className="min-w-0">
                                            <h4 className="truncate text-base font-semibold text-slate-900">{matriz.name}</h4>
                                            <p className="mt-1 text-xs text-slate-500">
                                                CNPJ: {matriz.cnpj || '--'} | Contratada em: {matriz.matrix_contracted_at || '--'}
                                            </p>
                                        </div>

                                        <div className="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                            <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Valor matriz</p>
                                            <p className="mt-1 text-base font-bold text-slate-900">
                                                {formatCurrency(matriz.matrix_monthly_value)}
                                            </p>
                                        </div>

                                        <div className="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                            <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Filiais</p>
                                            <p className="mt-1 text-base font-bold text-slate-900">{matriz.branches_count}</p>
                                        </div>

                                        <div className="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                            <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Total filiais</p>
                                            <p className="mt-1 text-base font-bold text-slate-900">
                                                {formatCurrency(matriz.branch_monthly_total)}
                                            </p>
                                        </div>

                                        <div className="rounded-2xl bg-white px-4 py-3 text-left shadow-sm xl:text-right">
                                            <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-700">Total mensal</p>
                                            <p className="mt-1 text-lg font-extrabold text-slate-900">
                                                {formatCurrency(matriz.total_monthly_value)}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-3 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                        <span className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                            Matriz
                                        </span>
                                        <span className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                            matriz.payment_status
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-rose-100 text-rose-700'
                                        }`}>
                                            {matriz.payment_status ? 'Pagamento pago' : 'Pagamento nao pago'}
                                        </span>
                                        <span className={`rounded-full px-3 py-1 text-xs font-semibold ${
                                            matriz.matrix_login_enabled
                                                ? 'bg-sky-100 text-sky-700'
                                                : 'bg-amber-100 text-amber-700'
                                        }`}>
                                            {matriz.matrix_login_enabled ? 'Login liberado' : 'Login bloqueado'}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => toggleMatrixPayment(matriz.id)}
                                            className={`rounded-2xl px-4 py-2 text-xs font-semibold text-white transition ${
                                                matriz.payment_status
                                                    ? 'bg-rose-600 hover:bg-rose-700'
                                                    : 'bg-emerald-600 hover:bg-emerald-700'
                                            }`}
                                        >
                                            {matriz.payment_status ? 'Marcar nao pago' : 'Marcar pago'}
                                        </button>
                                        {matriz.matrix_unit_id && (
                                            <button
                                                type="button"
                                                onClick={() => toggleUnitLogin(matriz.matrix_unit_id)}
                                                className={`rounded-2xl px-4 py-2 text-xs font-semibold text-white transition ${
                                                    matriz.matrix_login_enabled
                                                        ? 'bg-amber-600 hover:bg-amber-700'
                                                        : 'bg-sky-600 hover:bg-sky-700'
                                                }`}
                                            >
                                                {matriz.matrix_login_enabled ? 'Bloquear login da matriz' : 'Liberar login da matriz'}
                                            </button>
                                        )}
                                    </div>

                                    {matriz.branches?.length > 0 && (
                                        <div className="mt-3 overflow-x-auto">
                                            <table className="min-w-full text-xs">
                                                <thead>
                                                    <tr className="text-left text-slate-500">
                                                        <th className="px-3 py-2">Filial</th>
                                                        <th className="px-3 py-2">Status</th>
                                                        <th className="px-3 py-2">Contratada em</th>
                                                        <th className="px-3 py-2">Mensalidade</th>
                                                        <th className="px-3 py-2">Pagamento</th>
                                                        <th className="px-3 py-2">Login</th>
                                                        <th className="px-3 py-2">Acoes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {matriz.branches.map((branch) => (
                                                        <tr key={branch.id} className="border-t border-slate-200">
                                                            <td className="px-3 py-2 text-slate-700">{branch.name}</td>
                                                            <td className="px-3 py-2 text-slate-700">
                                                                {Number(branch.status) === 1 ? 'Ativa' : 'Inativa'}
                                                            </td>
                                                            <td className="px-3 py-2 text-slate-700">{branch.contracted_at || '--'}</td>
                                                            <td className="px-3 py-2 font-semibold text-slate-900">
                                                                {formatCurrency(branch.monthly_value)}
                                                            </td>
                                                            <td className="px-3 py-2">
                                                                <span className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${
                                                                    branch.payment_status
                                                                        ? 'bg-emerald-100 text-emerald-700'
                                                                        : 'bg-rose-100 text-rose-700'
                                                                }`}>
                                                                    {branch.payment_status ? 'Pago' : 'Nao pago'}
                                                                </span>
                                                            </td>
                                                            <td className="px-3 py-2">
                                                                <span className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${
                                                                    branch.login_enabled
                                                                        ? 'bg-sky-100 text-sky-700'
                                                                        : 'bg-amber-100 text-amber-700'
                                                                }`}>
                                                                    {branch.login_enabled ? 'Liberado' : 'Bloqueado'}
                                                                </span>
                                                            </td>
                                                            <td className="px-3 py-2">
                                                                <div className="flex flex-wrap gap-2">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => toggleUnitPayment(branch.id)}
                                                                        className={`rounded-xl px-3 py-1.5 text-[11px] font-semibold text-white transition ${
                                                                            branch.payment_status
                                                                                ? 'bg-rose-600 hover:bg-rose-700'
                                                                                : 'bg-emerald-600 hover:bg-emerald-700'
                                                                        }`}
                                                                    >
                                                                        {branch.payment_status ? 'Nao pago' : 'Pago'}
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => toggleUnitLogin(branch.id)}
                                                                        className={`rounded-xl px-3 py-1.5 text-[11px] font-semibold text-white transition ${
                                                                            branch.login_enabled
                                                                                ? 'bg-amber-600 hover:bg-amber-700'
                                                                                : 'bg-sky-600 hover:bg-sky-700'
                                                                        }`}
                                                                    >
                                                                        {branch.login_enabled ? 'Bloquear login' : 'Liberar login'}
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </article>
                            )) : (
                                <div className="rounded-3xl border border-dashed border-slate-300 px-5 py-10 text-center text-sm text-slate-500">
                                    Nenhuma matriz cadastrada ainda.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

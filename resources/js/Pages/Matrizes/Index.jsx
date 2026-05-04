import AlertMessage from "@/Components/Alert/AlertMessage";
import InfoButton from "@/Components/Button/InfoButton";
import SuccessButton from "@/Components/Button/SuccessButton";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { formatBrazilShortDate } from "@/Utils/date";
import { Head, Link, useForm, usePage } from "@inertiajs/react";

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

export default function MatrixIndex({ auth, matrizes = [], planSettings }) {
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

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Matrizes</h2>}
        >
            <Head title="Matrizes" />

            <div className="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="overflow-hidden bg-white shadow-lg sm:rounded-lg dark:bg-gray-800">
                    <div className="flex justify-between items-center m-4">
                        <h3 className="text-lg">Empresas</h3>
                        <Link href={route('matrizes.create')}>
                            <SuccessButton aria-label="Cadastrar matriz" title="Cadastrar matriz">
                                <i className="bi bi-plus-lg text-lg" aria-hidden="true"></i>
                            </SuccessButton>
                        </Link>
                    </div>

                    <AlertMessage message={flash} />

                    <form onSubmit={submit} className="mx-4 mb-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
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

                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">ID</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Matriz</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Aplicacao</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Unidades</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Usuarios</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Plano matriz</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Contratada em</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Pagamento</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Status</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Acoes</td>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            {matrizes.map((matriz) => (
                                <tr key={matriz.id}>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.id}</td>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.nome}</td>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.aplicacao?.tb28_nome || '--'}</td>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.units_count}</td>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.users_count}</td>
                                    <td className="px-4 py-2 text-sm font-semibold text-gray-700">
                                        {formatCurrency(matriz.plano_mensal_valor ?? 250)}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-500">
                                        {formatBrazilShortDate(matriz.plano_contratado_em)}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-500">
                                        {matriz.pagamento_ativo ? 'Ativo' : 'Bloqueado'}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-500">
                                        {Number(matriz.status) === 1 ? 'Ativa' : 'Inativa'}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-500">
                                        <Link href={route('matrizes.edit', matriz.id)}>
                                            <InfoButton aria-label={`Editar matriz ${matriz.nome}`} title="Editar matriz">
                                                <i className="bi bi-pencil-square text-lg" aria-hidden="true"></i>
                                            </InfoButton>
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

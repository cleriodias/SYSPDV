import AlertMessage from "@/Components/Alert/AlertMessage";
import SuccessButton from "@/Components/Button/SuccessButton";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, usePage } from "@inertiajs/react";

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

export default function MatrixIndex({ auth, matrizes = [] }) {
    const { flash } = usePage().props;

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

                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">ID</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Matriz</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">CNPJ</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Unidades</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Usuarios</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Plano matriz</td>
                                <td className="px-4 py-3 text-left text-sm font-medium text-gray-500 tracking-wider">Status</td>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            {matrizes.map((matriz) => (
                                <tr key={matriz.id}>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.id}</td>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.nome}</td>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.cnpj || '--'}</td>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.units_count}</td>
                                    <td className="px-4 py-2 text-sm text-gray-500">{matriz.users_count}</td>
                                    <td className="px-4 py-2 text-sm font-semibold text-gray-700">
                                        {formatCurrency(matriz.plano_mensal_valor ?? 250)}
                                    </td>
                                    <td className="px-4 py-2 text-sm text-gray-500">
                                        {Number(matriz.status) === 1 ? 'Ativa' : 'Inativa'}
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

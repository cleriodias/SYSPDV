import InfoButton from "@/Components/Button/InfoButton";
import SuccessButton from "@/Components/Button/SuccessButton";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, useForm } from "@inertiajs/react";

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

export default function MatrixCreate({ auth, applications = [], planSettings }) {
    const { data, setData, post, processing, errors } = useForm({
        matriz_nome: '',
        matriz_cnpj: '',
        tb28_id: applications[0]?.tb28_id ? String(applications[0].tb28_id) : '1',
        master_name: '',
        master_email: '',
        master_password: '',
        master_password_confirmation: '',
        unit_name: '',
        unit_address: '',
        unit_cep: '',
        unit_phone: '',
        unit_cnpj: '',
        unit_location: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('matrizes.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Matrizes</h2>}
        >
            <Head title="Nova Matriz" />

            <div className="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="overflow-hidden bg-white shadow-lg sm:rounded-lg dark:bg-gray-800">
                    <div className="flex justify-between items-center m-4">
                        <h3 className="text-lg">Cadastrar matriz</h3>
                        <Link href={route('matrizes.index')}>
                            <InfoButton aria-label="Listar matrizes" title="Listar matrizes">
                                <i className="bi bi-list text-lg" aria-hidden="true"></i>
                            </InfoButton>
                        </Link>
                    </div>

                    <div className="bg-gray-50 text-sm dark:bg-gray-700 p-4 rounded-lg shadow-m">
                        <form onSubmit={submit}>
                            <div className="mb-6">
                                <h4 className="text-base font-semibold text-gray-800">Dados da matriz</h4>
                                <p className="mt-2 text-sm text-emerald-700">
                                    Esta nova matriz sera contratada por {formatCurrency(planSettings?.matrix_monthly_price)} por mes.
                                </p>
                            </div>

                            <div className="mb-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Nome da matriz</label>
                                    <input
                                        type="text"
                                        value={data.matriz_nome}
                                        onChange={(e) => setData('matriz_nome', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.matriz_nome && <span className="text-red-600">{errors.matriz_nome}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">CNPJ da matriz</label>
                                    <input
                                        type="text"
                                        value={data.matriz_cnpj}
                                        onChange={(e) => setData('matriz_cnpj', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.matriz_cnpj && <span className="text-red-600">{errors.matriz_cnpj}</span>}
                                </div>
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700">Aplicacao</label>
                                <select
                                    value={data.tb28_id}
                                    onChange={(e) => setData('tb28_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                >
                                    {applications.map((application) => (
                                        <option key={application.tb28_id} value={application.tb28_id}>
                                            {application.tb28_nome}
                                        </option>
                                    ))}
                                </select>
                                {errors.tb28_id && <span className="text-red-600">{errors.tb28_id}</span>}
                            </div>

                            <div className="mb-6 mt-8">
                                <h4 className="text-base font-semibold text-gray-800">Usuario master da matriz</h4>
                            </div>

                            <div className="mb-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Nome</label>
                                    <input
                                        type="text"
                                        value={data.master_name}
                                        onChange={(e) => setData('master_name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.master_name && <span className="text-red-600">{errors.master_name}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">E-mail</label>
                                    <input
                                        type="email"
                                        value={data.master_email}
                                        onChange={(e) => setData('master_email', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.master_email && <span className="text-red-600">{errors.master_email}</span>}
                                </div>
                            </div>

                            <div className="mb-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Senha</label>
                                    <input
                                        type="password"
                                        value={data.master_password}
                                        onChange={(e) => setData('master_password', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.master_password && <span className="text-red-600">{errors.master_password}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Confirmar senha</label>
                                    <input
                                        type="password"
                                        value={data.master_password_confirmation}
                                        onChange={(e) => setData('master_password_confirmation', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                </div>
                            </div>

                            <div className="mb-6 mt-8">
                                <h4 className="text-base font-semibold text-gray-800">Unidade matriz</h4>
                                <p className="mt-2 text-sm text-gray-600">
                                    O valor da mensalidade sera gravado neste cadastro e nao mudara se o plano atual for alterado no futuro.
                                </p>
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700">Nome da unidade</label>
                                <input
                                    type="text"
                                    value={data.unit_name}
                                    onChange={(e) => setData('unit_name', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                {errors.unit_name && <span className="text-red-600">{errors.unit_name}</span>}
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700">Endereco</label>
                                <input
                                    type="text"
                                    value={data.unit_address}
                                    onChange={(e) => setData('unit_address', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                {errors.unit_address && <span className="text-red-600">{errors.unit_address}</span>}
                            </div>

                            <div className="mb-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">CEP</label>
                                    <input
                                        type="text"
                                        value={data.unit_cep}
                                        onChange={(e) => setData('unit_cep', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.unit_cep && <span className="text-red-600">{errors.unit_cep}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Telefone</label>
                                    <input
                                        type="text"
                                        value={data.unit_phone}
                                        onChange={(e) => setData('unit_phone', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.unit_phone && <span className="text-red-600">{errors.unit_phone}</span>}
                                </div>
                            </div>

                            <div className="mb-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">CNPJ da unidade</label>
                                    <input
                                        type="text"
                                        value={data.unit_cnpj}
                                        onChange={(e) => setData('unit_cnpj', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.unit_cnpj && <span className="text-red-600">{errors.unit_cnpj}</span>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Link Google Maps</label>
                                    <input
                                        type="text"
                                        value={data.unit_location}
                                        onChange={(e) => setData('unit_location', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.unit_location && <span className="text-red-600">{errors.unit_location}</span>}
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <SuccessButton type="submit" disabled={processing} className="text-sm" aria-label="Cadastrar matriz" title="Cadastrar matriz">
                                    <i className="bi bi-plus-lg text-lg" aria-hidden="true"></i>
                                </SuccessButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

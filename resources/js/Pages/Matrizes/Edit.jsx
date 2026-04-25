import AlertMessage from "@/Components/Alert/AlertMessage";
import InfoButton from "@/Components/Button/InfoButton";
import SuccessButton from "@/Components/Button/SuccessButton";
import WarningButton from "@/Components/Button/WarningButton";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {
    isoToBrazilShortDateInput,
    formatBrazilShortDate,
    shortBrazilDateInputToIso,
} from "@/Utils/date";
import { Head, Link, useForm, usePage } from "@inertiajs/react";

const formatCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

export default function MatrixEdit({ auth, matriz, matrixUnit, branchUnits = [] }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors, transform } = useForm({
        nome: matriz?.nome ?? '',
        cnpj: matriz?.cnpj ?? '',
        unit_name: matrixUnit?.tb2_nome ?? '',
        unit_address: matrixUnit?.tb2_endereco ?? '',
        unit_cep: matrixUnit?.tb2_cep ?? '',
        unit_phone: matrixUnit?.tb2_fone ?? '',
        unit_cnpj: matrixUnit?.tb2_cnpj ?? '',
        unit_location: matrixUnit?.tb2_localizacao ?? '',
        status: Number(matriz?.status ?? 1) === 1 ? '1' : '0',
        pagamento_ativo: matriz?.pagamento_ativo === false ? '0' : '1',
        plano_mensal_valor: matriz?.plano_mensal_valor ?? 0,
        plano_contratado_em: isoToBrazilShortDateInput(matriz?.plano_contratado_em ?? ''),
    });

    const contractedDateIso = shortBrazilDateInputToIso(data.plano_contratado_em);

    const submit = (e) => {
        e.preventDefault();

        transform((current) => ({
            ...current,
            status: current.status === '1',
            pagamento_ativo: current.pagamento_ativo === '1',
            plano_contratado_em: shortBrazilDateInputToIso(current.plano_contratado_em) || '',
        }));

        put(route('matrizes.update', matriz.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Matrizes</h2>}
        >
            <Head title={`Editar matriz ${matriz?.nome ?? ''}`} />

            <div className="py-4 max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="overflow-hidden bg-white shadow-lg sm:rounded-lg dark:bg-gray-800">
                    <div className="flex justify-between items-center m-4">
                        <div>
                            <h3 className="text-lg">Editar matriz</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                Atualize os dados da empresa e o valor de mensalidade contratado.
                            </p>
                        </div>

                        <Link href={route('matrizes.index')}>
                            <InfoButton aria-label="Listar matrizes" title="Listar matrizes">
                                <i className="bi bi-list text-lg" aria-hidden="true"></i>
                            </InfoButton>
                        </Link>
                    </div>

                    <AlertMessage message={flash} />

                    <div className="bg-gray-50 text-sm dark:bg-gray-700 p-4 rounded-lg shadow-m">
                        <form onSubmit={submit}>
                            <div className="mb-6 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-emerald-800">
                                <p className="text-xs font-bold uppercase tracking-[0.18em]">Plano da matriz</p>
                                <p className="mt-2 text-lg font-semibold">
                                    Mensalidade atual: {formatCurrency(data.plano_mensal_valor)}
                                </p>
                            </div>

                            <div className="mb-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Nome da matriz</label>
                                    <input
                                        type="text"
                                        value={data.nome}
                                        onChange={(e) => setData('nome', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.nome && <span className="text-red-600">{errors.nome}</span>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">CNPJ da matriz</label>
                                    <input
                                        type="text"
                                        value={data.cnpj}
                                        onChange={(e) => setData('cnpj', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.cnpj && <span className="text-red-600">{errors.cnpj}</span>}
                                </div>
                            </div>

                            <div className="mb-6 mt-8">
                                <h4 className="text-base font-semibold text-gray-800">Unidade matriz</h4>
                                <p className="mt-2 text-sm text-gray-600">
                                    Estes dados pertencem ao cadastro da unidade principal vinculada a esta matriz.
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

                            <div className="mb-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Valor da mensalidade</label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.plano_mensal_valor}
                                        onChange={(e) => setData('plano_mensal_valor', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    {errors.plano_mensal_valor && (
                                        <span className="text-red-600">{errors.plano_mensal_valor}</span>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Data de contratacao</label>
                                    <div className="relative mt-1">
                                        <div className="flex items-center rounded-md border border-gray-300 bg-white px-3 py-2">
                                            <span className="text-gray-900">
                                                {data.plano_contratado_em || 'DD/MM/AA'}
                                            </span>
                                            <span className="ml-auto text-gray-400">
                                                <i className="bi bi-calendar3" aria-hidden="true" />
                                            </span>
                                        </div>
                                        <input
                                            type="date"
                                            value={contractedDateIso}
                                            onChange={(e) =>
                                                setData('plano_contratado_em', isoToBrazilShortDateInput(e.target.value))
                                            }
                                            className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                            aria-label="Selecionar data de contratacao"
                                        />
                                    </div>
                                    {errors.plano_contratado_em && (
                                        <span className="text-red-600">{errors.plano_contratado_em}</span>
                                    )}
                                </div>
                            </div>

                            <div className="mb-6 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Status da matriz</label>
                                    <select
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    >
                                        <option value="1">Ativa</option>
                                        <option value="0">Inativa</option>
                                    </select>
                                    {errors.status && <span className="text-red-600">{errors.status}</span>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Pagamento</label>
                                    <select
                                        value={data.pagamento_ativo}
                                        onChange={(e) => setData('pagamento_ativo', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2"
                                    >
                                        <option value="1">Ativo</option>
                                        <option value="0">Bloqueado</option>
                                    </select>
                                    {errors.pagamento_ativo && (
                                        <span className="text-red-600">{errors.pagamento_ativo}</span>
                                    )}
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <SuccessButton type="submit" disabled={processing} className="text-sm" aria-label="Salvar matriz" title="Salvar matriz">
                                    <i className="bi bi-check-lg text-lg" aria-hidden="true"></i>
                                </SuccessButton>
                            </div>
                        </form>

                        <BranchMonthlyValueSection matriz={matriz} branchUnits={branchUnits} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function BranchMonthlyValueSection({ matriz, branchUnits }) {
    return (
        <div className="mt-8 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h4 className="text-base font-semibold text-gray-800">Mensalidade das filiais</h4>
                    <p className="mt-1 text-sm text-gray-600">
                        Ajuste individualmente o valor contratado de cada filial desta matriz.
                    </p>
                </div>
                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700">
                    {branchUnits.length} filiais
                </span>
            </div>

            {branchUnits.length === 0 ? (
                <div className="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    Nenhuma filial cadastrada para esta matriz.
                </div>
            ) : (
                <div className="mt-4 overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200">
                        <thead className="bg-slate-50">
                            <tr>
                                <th className="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Filial</th>
                                <th className="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Status</th>
                                <th className="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Contratada em</th>
                                <th className="px-3 py-2 text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Mensalidade</th>
                                <th className="px-3 py-2 text-right text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Valor atual</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 bg-white">
                            {branchUnits.map((branchUnit) => (
                                <BranchMonthlyValueRow key={branchUnit.tb2_id} matriz={matriz} branchUnit={branchUnit} />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

function BranchMonthlyValueRow({ matriz, branchUnit }) {
    const { data, setData, put, processing, errors } = useForm({
        plano_mensal_valor: branchUnit?.plano_mensal_valor ?? 0,
    });

    const submit = (event) => {
        event.preventDefault();

        put(route('matrizes.branches.monthly-value.update', {
            matriz: matriz.id,
            unit: branchUnit.tb2_id,
        }));
    };

    return (
        <tr>
            <td className="px-3 py-3 text-sm font-medium text-slate-700">{branchUnit.tb2_nome}</td>
            <td className="px-3 py-3 text-sm text-slate-600">
                {Number(branchUnit.tb2_status) === 1 ? 'Ativa' : 'Inativa'}
            </td>
            <td className="px-3 py-3 text-sm text-slate-600">
                {formatBrazilShortDate(branchUnit.plano_contratado_em) || '--'}
            </td>
            <td className="px-3 py-3 text-sm text-slate-600">
                <form onSubmit={submit} className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div className="flex min-w-[190px] items-center rounded-md border border-gray-300 bg-white px-3 py-2">
                        <span className="mr-2 text-sm font-medium text-slate-500">R$</span>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.plano_mensal_valor}
                            onChange={(event) => setData('plano_mensal_valor', event.target.value)}
                            className="w-full border-0 p-0 text-right text-sm text-slate-700 focus:outline-none focus:ring-0"
                        />
                    </div>
                    <WarningButton
                        type="submit"
                        disabled={processing}
                        className="justify-center text-sm"
                        aria-label={`Salvar mensalidade da filial ${branchUnit.tb2_nome}`}
                        title="Salvar mensalidade da filial"
                    >
                        <i className="bi bi-currency-dollar text-lg" aria-hidden="true"></i>
                    </WarningButton>
                </form>
                {errors.plano_mensal_valor && (
                    <span className="mt-1 block text-xs text-red-600">{errors.plano_mensal_valor}</span>
                )}
            </td>
            <td className="px-3 py-3 text-right text-sm text-slate-500">
                {formatCurrency(branchUnit.plano_mensal_valor)}
            </td>
        </tr>
    );
}

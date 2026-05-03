import AlertMessage from '@/Components/Alert/AlertMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const booleanOptions = [
    { value: true, label: 'Sim' },
    { value: false, label: 'Nao' },
];

const FieldError = ({ message }) =>
    message ? <p className="mt-1 text-sm text-rose-600">{message}</p> : null;

export default function Form({
    auth,
    mode,
    insurer,
    statusOptions = [],
}) {
    const { flash } = usePage().props;
    const isEditing = mode === 'edit';
    const { data, setData, post, put, processing, errors } = useForm({
        tb31_nome_fantasia: insurer?.nome_fantasia ?? '',
        tb31_razao_social: insurer?.razao_social ?? '',
        tb31_cnpj: insurer?.cnpj ?? '',
        tb31_codigo_susep: insurer?.codigo_susep ?? '',
        tb31_status: insurer?.status ?? '1',
        tb31_usa_integracao: insurer?.usa_integracao ?? false,
        tb31_codigo_externo_integracao: insurer?.codigo_externo_integracao ?? '',
        tb31_observacoes_operacionais: insurer?.observacoes_operacionais ?? '',
    });

    const submit = () => {
        if (isEditing) {
            put(route('nfe.insurers.update', { insurer: insurer.id }), {
                preserveScroll: true,
            });
            return;
        }

        post(route('nfe.insurers.store'), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {isEditing ? 'Editar seguradora' : 'Nova seguradora'}
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Cadastro auxiliar mestre da NFe - Corretora de Seguros.
                    </p>
                </div>
            )}
        >
            <Head title={isEditing ? 'Editar seguradora' : 'Nova seguradora'} />

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
                                        {isEditing ? data.tb31_nome_fantasia || 'Editar seguradora' : 'Nova seguradora'}
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-7 text-slate-200 sm:text-base">
                                        Cadastre seguradoras por matriz para usar selecao padronizada nos produtos de seguro, com suporte a status e integracao.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route('nfe.insurers.index')}
                                className="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100"
                            >
                                Voltar para seguradoras
                            </Link>
                        </div>
                    </section>

                    <section className="space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">Dados da seguradora</h3>
                            <p className="mt-1 text-sm text-slate-500">
                                Identificacao principal, status operacional e parametros de integracao.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div className="xl:col-span-2">
                                <label className="text-sm font-semibold text-slate-700">Nome fantasia</label>
                                <input
                                    type="text"
                                    value={data.tb31_nome_fantasia}
                                    onChange={(event) => setData('tb31_nome_fantasia', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb31_nome_fantasia} />
                            </div>

                            <div className="xl:col-span-2">
                                <label className="text-sm font-semibold text-slate-700">Razao social</label>
                                <input
                                    type="text"
                                    value={data.tb31_razao_social}
                                    onChange={(event) => setData('tb31_razao_social', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb31_razao_social} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">CNPJ</label>
                                <input
                                    type="text"
                                    value={data.tb31_cnpj}
                                    onChange={(event) => setData('tb31_cnpj', event.target.value.replace(/\D+/g, ''))}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb31_cnpj} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Codigo SUSEP ou interno</label>
                                <input
                                    type="text"
                                    value={data.tb31_codigo_susep}
                                    onChange={(event) => setData('tb31_codigo_susep', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb31_codigo_susep} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Status</label>
                                <select
                                    value={data.tb31_status}
                                    onChange={(event) => setData('tb31_status', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {statusOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb31_status} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Usa integracao</label>
                                <select
                                    value={data.tb31_usa_integracao ? '1' : '0'}
                                    onChange={(event) => setData('tb31_usa_integracao', event.target.value === '1')}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {booleanOptions.map((option) => (
                                        <option key={option.label} value={option.value ? '1' : '0'}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb31_usa_integracao} />
                            </div>

                            <div className="xl:col-span-2">
                                <label className="text-sm font-semibold text-slate-700">Codigo externo da integracao</label>
                                <input
                                    type="text"
                                    value={data.tb31_codigo_externo_integracao}
                                    onChange={(event) => setData('tb31_codigo_externo_integracao', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb31_codigo_externo_integracao} />
                            </div>

                            <div className="md:col-span-2 xl:col-span-4">
                                <label className="text-sm font-semibold text-slate-700">Observacoes operacionais</label>
                                <textarea
                                    rows={5}
                                    value={data.tb31_observacoes_operacionais}
                                    onChange={(event) => setData('tb31_observacoes_operacionais', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb31_observacoes_operacionais} />
                            </div>
                        </div>
                    </section>

                    <section className="rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-slate-900">Salvar seguradora</h3>
                                <p className="text-sm text-slate-500">
                                    Seguradoras inativas continuam no historico, mas nao podem ser selecionadas em novos produtos.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={submit}
                                disabled={processing}
                                className="rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {isEditing ? 'Salvar alteracoes' : 'Cadastrar seguradora'}
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

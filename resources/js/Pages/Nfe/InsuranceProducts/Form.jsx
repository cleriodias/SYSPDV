import AlertMessage from '@/Components/Alert/AlertMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const FieldError = ({ message }) =>
    message ? <p className="mt-1 text-sm text-rose-600">{message}</p> : null;

export default function Form({
    auth,
    mode,
    units = [],
    selectedUnitId = null,
    statusOptions = [],
    product,
}) {
    const { flash } = usePage().props;
    const isEditing = mode === 'edit';
    const { data, setData, post, put, processing, errors } = useForm({
        tb2_id: product?.unit_id ?? (selectedUnitId ? String(selectedUnitId) : ''),
        tb30_codigo: product?.codigo ?? '',
        tb30_nome: product?.nome ?? '',
        tb30_seguradora: product?.seguradora ?? '',
        tb30_ramo: product?.ramo ?? '',
        tb30_modalidade: product?.modalidade ?? '',
        tb30_tipo_contratacao: product?.tipo_contratacao ?? 'individual',
        tb30_periodicidade: product?.periodicidade ?? 'mensal',
        tb30_cfop: product?.cfop ?? '',
        tb30_ncm: product?.ncm ?? '',
        tb30_unidade_padrao: product?.unidade_padrao ?? 'UN',
        tb30_premio_base: product?.premio_base ?? '0.00',
        tb30_comissao_percentual: product?.comissao_percentual ?? '0.00',
        tb30_regras: product?.regras ?? '',
        tb30_status: product?.status ?? '1',
    });

    const submit = () => {
        if (isEditing) {
            put(route('nfe.insurance-products.update', { insuranceProduct: product.id }), {
                preserveScroll: true,
            });
            return;
        }

        post(route('nfe.insurance-products.store'), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {isEditing ? 'Editar produto de seguro' : 'Novo produto de seguro'}
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Cadastro proprio da NFe voltado para seguradora, ramo e premio.
                    </p>
                </div>
            )}
        >
            <Head title={isEditing ? 'Editar produto de seguro' : 'Novo produto de seguro'} />

            <div className="py-8">
                <div className="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <section className="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-900 p-6 text-white shadow-xl">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div className="space-y-3">
                                <span className="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-slate-100">
                                    Produto de Seguro
                                </span>
                                <div className="space-y-2">
                                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                                        {isEditing ? data.tb30_nome || 'Editar cadastro' : 'Novo cadastro de seguro'}
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-7 text-slate-200 sm:text-base">
                                        Defina seguradora, ramo, contratacao, premio e comissao para abastecer os lancamentos da aplicacao NFe.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route('nfe.insurance-products.index', selectedUnitId ? { unit_id: selectedUnitId } : {})}
                                className="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100"
                            >
                                Voltar para produtos
                            </Link>
                        </div>
                    </section>

                    <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label className="text-sm font-semibold text-slate-700">Escopo</label>
                                <select
                                    value={data.tb2_id}
                                    onChange={(event) => setData('tb2_id', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    <option value="">Matriz inteira</option>
                                    {units.map((unit) => (
                                        <option key={unit.id} value={unit.id}>
                                            {unit.name}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb2_id} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Codigo interno</label>
                                <input
                                    type="text"
                                    value={data.tb30_codigo}
                                    onChange={(event) => setData('tb30_codigo', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_codigo} />
                            </div>

                            <div className="xl:col-span-2">
                                <label className="text-sm font-semibold text-slate-700">Nome do produto</label>
                                <input
                                    type="text"
                                    value={data.tb30_nome}
                                    onChange={(event) => setData('tb30_nome', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_nome} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Seguradora</label>
                                <input
                                    type="text"
                                    value={data.tb30_seguradora}
                                    onChange={(event) => setData('tb30_seguradora', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_seguradora} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Ramo</label>
                                <input
                                    type="text"
                                    value={data.tb30_ramo}
                                    onChange={(event) => setData('tb30_ramo', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_ramo} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Modalidade</label>
                                <input
                                    type="text"
                                    value={data.tb30_modalidade}
                                    onChange={(event) => setData('tb30_modalidade', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_modalidade} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Tipo de contratacao</label>
                                <select
                                    value={data.tb30_tipo_contratacao}
                                    onChange={(event) => setData('tb30_tipo_contratacao', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    <option value="individual">Individual</option>
                                    <option value="coletiva">Coletiva</option>
                                    <option value="mensal">Mensal</option>
                                    <option value="anual">Anual</option>
                                </select>
                                <FieldError message={errors.tb30_tipo_contratacao} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Periodicidade</label>
                                <select
                                    value={data.tb30_periodicidade}
                                    onChange={(event) => setData('tb30_periodicidade', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    <option value="mensal">Mensal</option>
                                    <option value="trimestral">Trimestral</option>
                                    <option value="semestral">Semestral</option>
                                    <option value="anual">Anual</option>
                                    <option value="unica">Parcela unica</option>
                                </select>
                                <FieldError message={errors.tb30_periodicidade} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">CFOP padrao</label>
                                <input
                                    type="text"
                                    value={data.tb30_cfop}
                                    onChange={(event) => setData('tb30_cfop', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_cfop} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">NCM padrao</label>
                                <input
                                    type="text"
                                    value={data.tb30_ncm}
                                    onChange={(event) => setData('tb30_ncm', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_ncm} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Unidade padrao</label>
                                <input
                                    type="text"
                                    value={data.tb30_unidade_padrao}
                                    onChange={(event) => setData('tb30_unidade_padrao', event.target.value.toUpperCase())}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm uppercase text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_unidade_padrao} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Premio base</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.tb30_premio_base}
                                    onChange={(event) => setData('tb30_premio_base', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_premio_base} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Comissao padrao (%)</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value={data.tb30_comissao_percentual}
                                    onChange={(event) => setData('tb30_comissao_percentual', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_comissao_percentual} />
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-slate-700">Status</label>
                                <select
                                    value={data.tb30_status}
                                    onChange={(event) => setData('tb30_status', event.target.value)}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                                    {statusOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.tb30_status} />
                            </div>

                            <div className="md:col-span-2 xl:col-span-4">
                                <label className="text-sm font-semibold text-slate-700">Regras e observacoes operacionais</label>
                                <textarea
                                    value={data.tb30_regras}
                                    onChange={(event) => setData('tb30_regras', event.target.value)}
                                    rows={5}
                                    className="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                />
                                <FieldError message={errors.tb30_regras} />
                            </div>
                        </div>
                    </section>

                    <section className="rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-slate-900">Salvar cadastro</h3>
                                <p className="text-sm text-slate-500">
                                    Este produto passara a abastecer os lancamentos da aplicacao NFe no contexto de seguros.
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    onClick={submit}
                                    disabled={processing}
                                    className="rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {isEditing ? 'Salvar alteracoes' : 'Cadastrar produto'}
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

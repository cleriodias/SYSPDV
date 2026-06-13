import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/Button/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

const buildIfoodFormData = (configuration = {}, selectedUnitId = null) => ({
    tb2_id: configuration?.tb2_id ?? selectedUnitId ?? '',
    tb33_ativo: Boolean(configuration?.tb33_ativo),
    tb33_ambiente: configuration?.tb33_ambiente ?? 'homologacao',
    tb33_nome_loja: configuration?.tb33_nome_loja ?? '',
    tb33_merchant_id: configuration?.tb33_merchant_id ?? '',
    tb33_client_id: configuration?.tb33_client_id ?? '',
    tb33_client_secret: '',
    tb33_authorization_code: '',
    tb33_webhook_token: configuration?.tb33_webhook_token ?? '',
    tb33_observacoes: configuration?.tb33_observacoes ?? '',
});

const inputClassName =
    'mt-2 block w-full rounded-2xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';

const statusMeta = (active) =>
    active
        ? {
              label: 'Ativo para o caixa',
              className: 'border-emerald-200 bg-emerald-50 text-emerald-800',
          }
        : {
              label: 'Desativado',
              className: 'border-slate-200 bg-slate-50 text-slate-700',
          };

export default function IfoodConfig({
    units = [],
    selectedUnitId = null,
    unit = null,
    configuration = {},
    environmentOptions = [],
}) {
    const ifoodFormData = buildIfoodFormData(configuration, selectedUnitId);
    const ifoodFormDataKey = JSON.stringify(ifoodFormData);
    const { data, setData, put, processing, errors, clearErrors } = useForm(
        ifoodFormData,
    );
    const formSetDataRef = useRef(setData);
    const formClearErrorsRef = useRef(clearErrors);
    const lastSyncedIfoodFormKeyRef = useRef(null);

    const status = statusMeta(data.tb33_ativo);

    useEffect(() => {
        formSetDataRef.current = setData;
        formClearErrorsRef.current = clearErrors;
    });

    useEffect(() => {
        if (lastSyncedIfoodFormKeyRef.current === ifoodFormDataKey) {
            return;
        }

        lastSyncedIfoodFormKeyRef.current = ifoodFormDataKey;
        formSetDataRef.current(ifoodFormData);
        formClearErrorsRef.current();
    }, [ifoodFormData, ifoodFormDataKey]);

    const handleSubmit = (event) => {
        event.preventDefault();
        put(route('settings.ifood.update'), {
            preserveScroll: true,
        });
    };

    const handleUnitChange = (unitId) => {
        router.get(
            route('settings.ifood'),
            { unit_id: unitId },
            {
                preserveState: false,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        Configuracao iFood
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Configure a unidade da Padaria e ligue a funcao para o caixa.
                    </p>
                </div>
            }
        >
            <Head title="Configuracao iFood" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
                        <div className="flex flex-col gap-4">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Unidade
                                </h3>
                                <p className="text-sm text-gray-500 dark:text-gray-300">
                                    A configuracao do iFood fica isolada por unidade.
                                </p>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {units.map((store) => {
                                    const isActive = Number(selectedUnitId) === Number(store.id);

                                    return (
                                        <button
                                            key={store.id}
                                            type="button"
                                            onClick={() => handleUnitChange(store.id)}
                                            className={`rounded-2xl border px-4 py-4 text-left transition ${
                                                isActive
                                                    ? 'border-blue-500 bg-blue-50 shadow-sm dark:border-blue-400 dark:bg-blue-500/10'
                                                    : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50/50 dark:border-gray-700 dark:bg-gray-900/40 dark:hover:border-blue-500/50'
                                            }`}
                                        >
                                            <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {store.name}
                                            </p>
                                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                                CNPJ: {store.cnpj || '--'}
                                            </p>
                                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                                {store.endereco || 'Endereco nao informado'}
                                            </p>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </section>

                    {!selectedUnitId ? (
                        <section className="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 shadow dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Selecione uma unidade para configurar o iFood.
                        </section>
                    ) : (
                        <div className="grid gap-6 xl:grid-cols-[1.55fr_0.95fr]">
                            <form
                                onSubmit={handleSubmit}
                                className="rounded-2xl bg-white p-6 shadow dark:bg-gray-800"
                            >
                                <div className="flex flex-col gap-3 border-b border-gray-200 pb-5 dark:border-gray-700">
                                    <div className="flex flex-wrap items-center gap-3">
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            Dados da integracao
                                        </h3>
                                        <span
                                            className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${status.className}`}
                                        >
                                            {status.label}
                                        </span>
                                    </div>
                                    <p className="text-sm text-gray-500 dark:text-gray-300">
                                        Quando ativa, a unidade fica sinalizada para uso do iFood no fluxo do caixa.
                                    </p>
                                </div>

                                <div className="mt-6 grid gap-5 md:grid-cols-2">
                                    <div className="md:col-span-2">
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Disponivel para o caixa
                                        </label>
                                        <select
                                            value={data.tb33_ativo ? '1' : '0'}
                                            onChange={(event) => setData('tb33_ativo', event.target.value === '1')}
                                            className={inputClassName}
                                        >
                                            <option value="0">Nao</option>
                                            <option value="1">Sim</option>
                                        </select>
                                        <InputError message={errors.tb33_ativo} className="mt-2" />
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Ambiente
                                        </label>
                                        <select
                                            value={data.tb33_ambiente}
                                            onChange={(event) => setData('tb33_ambiente', event.target.value)}
                                            className={inputClassName}
                                        >
                                            {environmentOptions.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.tb33_ambiente} className="mt-2" />
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Nome da loja no iFood
                                        </label>
                                        <input
                                            type="text"
                                            value={data.tb33_nome_loja}
                                            onChange={(event) => setData('tb33_nome_loja', event.target.value)}
                                            className={inputClassName}
                                            placeholder="Ex.: Padaria Centro"
                                        />
                                        <InputError message={errors.tb33_nome_loja} className="mt-2" />
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Merchant ID
                                        </label>
                                        <input
                                            type="text"
                                            value={data.tb33_merchant_id}
                                            onChange={(event) => setData('tb33_merchant_id', event.target.value)}
                                            className={inputClassName}
                                            placeholder="Identificador da loja no iFood"
                                        />
                                        <InputError message={errors.tb33_merchant_id} className="mt-2" />
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Client ID
                                        </label>
                                        <input
                                            type="text"
                                            value={data.tb33_client_id}
                                            onChange={(event) => setData('tb33_client_id', event.target.value)}
                                            className={inputClassName}
                                            placeholder="Client ID da integracao"
                                        />
                                        <InputError message={errors.tb33_client_id} className="mt-2" />
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Client Secret
                                        </label>
                                        <input
                                            type="password"
                                            value={data.tb33_client_secret}
                                            onChange={(event) => setData('tb33_client_secret', event.target.value)}
                                            className={inputClassName}
                                            placeholder="Preencha apenas para gravar ou trocar"
                                        />
                                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-300">
                                            Estado atual: {configuration?.client_secret_mask ?? 'Nao configurado'}.
                                        </p>
                                        <InputError message={errors.tb33_client_secret} className="mt-2" />
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Authorization Code
                                        </label>
                                        <textarea
                                            value={data.tb33_authorization_code}
                                            onChange={(event) => setData('tb33_authorization_code', event.target.value)}
                                            className={`${inputClassName} min-h-[120px]`}
                                            placeholder="Opcional nesta etapa. Deixe em branco para manter o que ja estiver salvo."
                                        />
                                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-300">
                                            Estado atual: {configuration?.authorization_code_mask ?? 'Nao configurado'}.
                                        </p>
                                        <InputError message={errors.tb33_authorization_code} className="mt-2" />
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Token de webhook
                                        </label>
                                        <input
                                            type="text"
                                            value={data.tb33_webhook_token}
                                            onChange={(event) => setData('tb33_webhook_token', event.target.value)}
                                            className={inputClassName}
                                            placeholder="Opcional para a segunda etapa"
                                        />
                                        <InputError message={errors.tb33_webhook_token} className="mt-2" />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Observacoes internas
                                        </label>
                                        <textarea
                                            value={data.tb33_observacoes}
                                            onChange={(event) => setData('tb33_observacoes', event.target.value)}
                                            className={`${inputClassName} min-h-[120px]`}
                                            placeholder="Anote alguma orientacao especifica desta unidade."
                                        />
                                        <InputError message={errors.tb33_observacoes} className="mt-2" />
                                    </div>
                                </div>

                                <div className="mt-6 flex justify-end">
                                    <PrimaryButton
                                        type="submit"
                                        disabled={processing}
                                        className="px-5 py-3 text-sm font-semibold normal-case tracking-normal"
                                    >
                                        Salvar configuracao
                                    </PrimaryButton>
                                </div>
                            </form>

                            <aside className="space-y-6">
                                <section className="rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        Resumo da unidade
                                    </h3>
                                    <div className="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                                        <p>
                                            <span className="font-semibold text-gray-900 dark:text-gray-100">Loja:</span>{' '}
                                            {unit?.name ?? '--'}
                                        </p>
                                        <p>
                                            <span className="font-semibold text-gray-900 dark:text-gray-100">CNPJ:</span>{' '}
                                            {unit?.cnpj ?? '--'}
                                        </p>
                                        <p>
                                            <span className="font-semibold text-gray-900 dark:text-gray-100">Endereco:</span>{' '}
                                            {unit?.endereco ?? '--'}
                                        </p>
                                        <p>
                                            <span className="font-semibold text-gray-900 dark:text-gray-100">Ultima atualizacao:</span>{' '}
                                            {configuration?.updated_at ?? '--'}
                                        </p>
                                    </div>
                                </section>

                                <section className="rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        O que esta entrega habilita
                                    </h3>
                                    <ul className="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                                        <li>Configura os dados da unidade para o iFood.</li>
                                        <li>Ativa ou desativa a funcao para o caixa da loja.</li>
                                        <li>Protege os campos sensiveis no banco.</li>
                                        <li>Prepara a base para a futura sincronizacao real de pedidos.</li>
                                    </ul>
                                </section>
                            </aside>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

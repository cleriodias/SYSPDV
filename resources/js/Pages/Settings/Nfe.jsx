import AlertMessage from '@/Components/Alert/AlertMessage';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { buildFiscalReceiptHtml, formatReceiptCurrency } from '@/Utils/receipt';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const STATUS_CLASS = {
    pendente_configuracao: 'border-amber-200 bg-amber-50 text-amber-800',
    erro_validacao: 'border-rose-200 bg-rose-50 text-rose-800',
    erro_transmissao: 'border-rose-200 bg-rose-50 text-rose-800',
    pendente_emissao: 'border-blue-200 bg-blue-50 text-blue-800',
    xml_assinado: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    emitida: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    cancelada: 'border-slate-200 bg-slate-100 text-slate-700',
};

const STATUS_LABEL = {
    pendente_configuracao: 'Pendente configuracao',
    erro_validacao: 'Erro de validacao',
    erro_transmissao: 'Erro de transmissao',
    pendente_emissao: 'Pendente emissao',
    xml_assinado: 'XML assinado',
    emitida: 'Emitida',
    cancelada: 'Cancelada',
};

const badgeClassName = (status) =>
    `inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${
        STATUS_CLASS[status] ?? STATUS_CLASS.pendente_configuracao
    }`;

const InvoiceTable = ({
    title,
    description,
    invoices = [],
    showTransmit = false,
    showReceipt = false,
    onOpenReceipt = null,
    hideHeader = false,
    wrapperClassName = 'rounded-2xl bg-white p-0 shadow dark:bg-gray-800',
    signedMode = 'signed',
}) => {
    const invoiceItems = Array.isArray(invoices)
        ? invoices
        : (Array.isArray(invoices?.data) ? invoices.data : []);

    return (
        <section className={wrapperClassName}>
        {!hideHeader && (
            <div className="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">{title}</h3>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-300">{description}</p>
            </div>
        )}

        {invoiceItems.length === 0 ? (
            <p className="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-300">
                Nenhuma nota encontrada nesta coluna.
            </p>
        ) : (
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead className="bg-gray-100 dark:bg-gray-900/60">
                        <tr>
                            <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Status</th>
                            <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Criada em</th>
                            <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Regenerar</th>
                            <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">XML</th>
                            {showTransmit && (
                                <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Transmissao</th>
                            )}
                            {showReceipt && (
                                <th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Cupom fiscal</th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                        {invoiceItems.map((invoice) => (
                            <tr key={invoice.id} title={invoice.mensagem ?? ''}>
                                <td className="px-3 py-3">
                                    <span className={badgeClassName(invoice.status)}>
                                        {STATUS_LABEL[invoice.status] ?? invoice.status}
                                    </span>
                                </td>
                                <td className="px-3 py-3 text-gray-700 dark:text-gray-200">{invoice.criada_em ?? '--'}</td>
                                <td className="px-3 py-3 text-gray-700 dark:text-gray-200">
                                    {invoice.pode_regenerar ? (
                                        <Link
                                            href={route('settings.fiscal.invoices.regenerate', {
                                                notaFiscal: invoice.id,
                                                origin: 'nfe',
                                                signed_mode: signedMode,
                                            })}
                                            method="post"
                                            as="button"
                                            className="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"
                                        >
                                            Regenerar nota
                                        </Link>
                                    ) : (
                                        '--'
                                    )}
                                </td>
                                <td className="px-3 py-3 text-gray-700 dark:text-gray-200">
                                    {invoice.xml_disponivel ? (
                                        <a
                                            href={route('settings.fiscal.invoices.xml', { notaFiscal: invoice.id })}
                                            className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                                        >
                                            Baixar XML
                                        </a>
                                    ) : (
                                        '--'
                                    )}
                                </td>
                                {showTransmit && (
                                    <td className="px-3 py-3 text-gray-700 dark:text-gray-200">
                                        {invoice.status === 'xml_assinado' ? (
                                            <Link
                                                href={route('settings.fiscal.invoices.transmit', {
                                                    notaFiscal: invoice.id,
                                                    origin: 'nfe',
                                                    signed_mode: signedMode,
                                                })}
                                                method="post"
                                                as="button"
                                                className="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"
                                            >
                                                Transmitir
                                            </Link>
                                        ) : (
                                            '--'
                                        )}
                                    </td>
                                )}
                                {showReceipt && (
                                    <td className="px-3 py-3 text-gray-700 dark:text-gray-200">
                                        {invoice.fiscal_receipt ? (
                                            <button
                                                type="button"
                                                onClick={() => onOpenReceipt?.(invoice.fiscal_receipt)}
                                                className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700"
                                            >
                                                Abrir cupom
                                            </button>
                                        ) : (
                                            '--'
                                        )}
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        )}
    </section>
    );
};

export default function Nfe({
    auth,
    units = [],
    selectedUnitId = null,
    errorInvoices = [],
    signedInvoices = [],
    issuedInvoices = [],
    fiscalUnavailableMessage = null,
    invoiceLoadWarning = null,
    signedMode = 'signed',
}) {
    const { flash = {} } = usePage().props;
    const [activeSignedMode, setActiveSignedMode] = useState(signedMode);
    const [selectedFiscalReceipt, setSelectedFiscalReceipt] = useState(null);
    const [printError, setPrintError] = useState('');

    useEffect(() => {
        setActiveSignedMode(signedMode);
    }, [signedMode]);

    const rightInvoices = activeSignedMode === 'issued' ? issuedInvoices : signedInvoices;

    const handlePrintFiscalReceipt = (receipt) => {
        setPrintError('');

        if (!receipt) {
            setPrintError('Nao foi possivel montar os dados do cupom fiscal desta nota.');
            return;
        }

        const printWindow = window.open('', '_blank', 'width=400,height=600');

        if (!printWindow) {
            setPrintError('Permita pop-ups para imprimir o cupom fiscal.');
            return;
        }

        printWindow.document.write(buildFiscalReceiptHtml(receipt));
        printWindow.document.close();
    };

    const handleSelectUnit = (unitId) => {
        router.get(route('settings.nfe'), {
            unit_id: unitId,
            signed_mode: activeSignedMode,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSignedModeChange = (mode) => {
        if (mode === activeSignedMode) {
            return;
        }

        setActiveSignedMode(mode);
        router.get(route('settings.nfe'), {
            unit_id: selectedUnitId,
            signed_mode: mode,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={(
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">NFe - Corretora de Seguros</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Acompanhe as ultimas notas preparadas pela aplicacao NFe - Corretora de Seguros por unidade.
                    </p>
                </div>
            )}
        >
            <Head title="NFe - Corretora de Seguros" />
            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <AlertMessage message={flash} />

                    <section className="rounded-2xl bg-white p-6 shadow dark:bg-gray-800">
                        <div className="flex flex-col gap-3">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Unidade</h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-300">
                                        Selecione a loja pelos botoes abaixo.
                                    </p>
                                </div>
                                <Link
                                    href={route('settings.fiscal')}
                                    className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/20"
                                >
                                    Abrir configuracao fiscal
                                </Link>
                            </div>
                            <div>
                                <div className="flex flex-wrap gap-3">
                                {units.map((store) => {
                                    const isActive = Number(selectedUnitId) === Number(store.id);

                                    return (
                                        <button
                                            key={store.id}
                                            type="button"
                                            onClick={() => handleSelectUnit(store.id)}
                                            className={`rounded-full border px-5 py-3 text-sm font-semibold transition ${
                                                isActive
                                                    ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm dark:border-blue-400 dark:bg-blue-500/10 dark:text-blue-200'
                                                    : 'border-gray-200 bg-white text-gray-700 hover:border-blue-300 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-500/50 dark:hover:text-blue-200'
                                            }`}
                                        >
                                            {store.name}
                                        </button>
                                    );
                                })}
                                </div>
                            </div>
                        </div>
                    </section>

                    {!selectedUnitId ? (
                        <div className="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-sm text-gray-500 shadow dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Selecione uma unidade para visualizar as notas fiscais.
                        </div>
                    ) : (
                        <>
                            {fiscalUnavailableMessage && (
                                <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 shadow dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                                    {fiscalUnavailableMessage}
                                </div>
                            )}

                            {invoiceLoadWarning && (
                                <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 shadow dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                                    {invoiceLoadWarning}
                                </div>
                            )}

                            {printError && (
                                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                                    {printError}
                                </div>
                            )}

                            <section className="grid gap-6 xl:grid-cols-2">
                                <InvoiceTable
                                    title="Com erro"
                                    description="Notas com erro de validacao ou transmissao."
                                    invoices={errorInvoices}
                                    signedMode={activeSignedMode}
                                />
                                <section className="rounded-2xl bg-white p-0 shadow dark:bg-gray-800">
                                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                                        <div>
                                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                                                {signedMode === 'issued' ? 'Emitidas' : 'Assinadas'}
                                            </h3>
                                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                                {signedMode === 'issued'
                                                    ? 'Notas emitidas.'
                                                    : 'Notas assinadas.'}
                                            </p>
                                        </div>
                                        <div className="inline-flex rounded-full border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-900/50">
                                            <button
                                            type="button"
                                            onClick={() => handleSignedModeChange('signed')}
                                            className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                                                    activeSignedMode === 'signed'
                                                        ? 'bg-white text-blue-700 shadow-sm dark:bg-gray-800 dark:text-blue-200'
                                                        : 'text-slate-600 hover:text-blue-700 dark:text-slate-300 dark:hover:text-blue-200'
                                                }`}
                                            >
                                                Assinadas
                                            </button>
                                            <button
                                            type="button"
                                            onClick={() => handleSignedModeChange('issued')}
                                            className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                                                    activeSignedMode === 'issued'
                                                        ? 'bg-white text-blue-700 shadow-sm dark:bg-gray-800 dark:text-blue-200'
                                                        : 'text-slate-600 hover:text-blue-700 dark:text-slate-300 dark:hover:text-blue-200'
                                                }`}
                                            >
                                                Emitidas
                                            </button>
                                        </div>
                                    </div>

                                    <div className="p-0">
                                        <InvoiceTable
                                            title=""
                                            description=""
                                            invoices={rightInvoices}
                                            showTransmit={activeSignedMode === 'signed'}
                                            showReceipt={activeSignedMode === 'issued'}
                                            onOpenReceipt={setSelectedFiscalReceipt}
                                            hideHeader
                                            wrapperClassName="rounded-none bg-transparent p-0 shadow-none dark:bg-transparent"
                                            signedMode={activeSignedMode}
                                        />

                                        {rightInvoices?.links?.length > 0 && (
                                            <Pagination
                                                links={rightInvoices.links}
                                                currentPage={rightInvoices.current_page}
                                            />
                                        )}
                                    </div>
                                </section>
                            </section>
                        </>
                    )}
                </div>
            </div>

            <Modal show={Boolean(selectedFiscalReceipt)} onClose={() => setSelectedFiscalReceipt(null)} maxWidth="2xl" tone="light">
                <div className="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900">
                            {selectedFiscalReceipt?.model_label ?? 'Documento Fiscal'}
                        </h3>
                        <p className="text-sm text-gray-500">
                            {selectedFiscalReceipt?.issued_at ?? '--'}
                        </p>
                        <p className="text-xs font-semibold text-gray-600">
                            Loja: {selectedFiscalReceipt?.emitter_name ?? '---'}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setSelectedFiscalReceipt(null)}
                        className="text-sm font-semibold text-gray-500 hover:text-gray-800"
                    >
                        Fechar
                    </button>
                </div>

                <div className="max-h-[70vh] overflow-y-auto px-6 py-4">
                    <div className="space-y-2 text-sm text-gray-700">
                        <p><span className="font-medium">Tipo:</span> {selectedFiscalReceipt?.consumer_type === 'cupom_fiscal' ? 'Cupom Fiscal' : selectedFiscalReceipt?.consumer_type === 'consumidor' ? 'NF Consumidor' : 'NF Balcao'}</p>
                        <p><span className="font-medium">Pagamento:</span> {selectedFiscalReceipt?.payment_label ?? '--'}</p>
                        <p><span className="font-medium">Numero:</span> {selectedFiscalReceipt?.number ?? '--'}</p>
                        <p><span className="font-medium">Serie:</span> {selectedFiscalReceipt?.serie ?? '--'}</p>
                        <p><span className="font-medium">Status:</span> {selectedFiscalReceipt?.status ?? '--'}</p>
                        {selectedFiscalReceipt?.consumer_name && (
                            <p><span className="font-medium">Consumidor:</span> {selectedFiscalReceipt.consumer_name}</p>
                        )}
                        {selectedFiscalReceipt?.consumer_document && (
                            <p><span className="font-medium">Documento:</span> {selectedFiscalReceipt.consumer_document}</p>
                        )}
                        {selectedFiscalReceipt?.access_key && (
                            <p className="break-all"><span className="font-medium">Chave:</span> {selectedFiscalReceipt.access_key}</p>
                        )}
                        <p className="text-lg font-bold text-emerald-600">
                            Total: {formatReceiptCurrency(selectedFiscalReceipt?.total ?? 0)}
                        </p>
                    </div>

                    <div className="mt-6 rounded-2xl border border-gray-100 bg-gray-50 p-4">
                        <h4 className="text-sm font-semibold text-gray-700">Itens</h4>
                        <div className="mt-3 space-y-3 text-sm">
                            {(selectedFiscalReceipt?.items || []).map((item, index) => (
                                <div
                                    key={item.id ?? `fiscal-item-${index}`}
                                    className="flex items-center justify-between rounded-xl bg-white px-3 py-2 shadow-sm"
                                >
                                    <div>
                                        <p className="font-medium text-gray-900">
                                            {item.quantity}x {item.product_name}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {formatReceiptCurrency(item.unit_price)} cada
                                        </p>
                                    </div>
                                    <p className="font-semibold text-gray-900">
                                        {formatReceiptCurrency(item.subtotal)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <button
                        type="button"
                        onClick={() => setSelectedFiscalReceipt(null)}
                        className="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                    >
                        Fechar
                    </button>
                    <button
                        type="button"
                        onClick={() => handlePrintFiscalReceipt(selectedFiscalReceipt)}
                        className="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700"
                    >
                        Imprimir Fiscal
                    </button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

const TONE_CLASS_MAP = {
    Dark: 'border-slate-800 bg-slate-800 text-white dark:border-slate-200 dark:bg-slate-200 dark:text-slate-950',
    Primary: 'border-blue-500 bg-blue-500 text-white dark:border-blue-200 dark:bg-blue-200 dark:text-blue-950',
    Info: 'border-cyan-500 bg-cyan-500 text-white dark:border-cyan-200 dark:bg-cyan-200 dark:text-cyan-950',
    Success: 'border-green-500 bg-green-500 text-white dark:border-green-200 dark:bg-green-200 dark:text-green-950',
    Error: 'border-red-500 bg-red-500 text-white dark:border-red-200 dark:bg-red-200 dark:text-red-950',
};

export default function Config({ auth }) {
    const role = Number(auth?.user?.funcao ?? -1);
    const isMaster = role === 0;

    const options = [
        {
            label: 'Menu',
            icon: 'bi-ui-checks',
            href: route('settings.menu'),
            tone: 'Dark',
        },
        {
            label: 'Relatorios',
            icon: 'bi-clipboard-data',
            href: route('reports.index'),
            tone: 'Dark',
        },
        {
            label: 'Usuarios',
            icon: 'bi-people-fill',
            href: route('users.index'),
            tone: 'Primary',
        },
        {
            label: 'Unidades',
            icon: 'bi-building',
            href: route('units.index'),
            tone: 'Primary',
        },
        {
            label: 'Trocar',
            icon: 'bi-arrow-left-right',
            href: route('reports.switch-unit'),
            tone: 'Info',
        },
        {
            label: 'Permissoes de Menu',
            icon: 'bi-gear',
            href: route('settings.profile-access'),
            tone: 'Dark',
        },
        {
            label: 'Organizar Menu',
            icon: 'bi-list-ol',
            href: route('settings.menu-order'),
            tone: 'Error',
        },
        {
            label: 'Relatorio Gastos',
            icon: 'bi-receipt',
            href: route('reports.gastos'),
            tone: 'Success',
        },
        {
            label: 'Configuracao do Discarte',
            icon: 'bi-percent',
            href: route('settings.discard-config'),
            tone: 'Success',
        },
        {
            label: 'Controle de Pagamentos',
            icon: 'bi-cash-coin',
            href: route('settings.payment-control'),
            tone: 'Success',
        },
        {
            label: 'Configuracao Fiscal',
            icon: 'bi-receipt-cutoff',
            href: route('settings.fiscal'),
            tone: 'Error',
        },
        {
            label: 'Contra-Cheque',
            icon: 'bi-receipt-cutoff',
            href: route('settings.contra-cheque'),
            tone: 'Success',
        },
        {
            label: 'Folha de Pagamento',
            icon: 'bi-receipt',
            href: route('settings.payroll'),
            tone: 'Success',
        },
    ];

    if (isMaster) {
        options.push(
            {
                label: 'Banco de dados',
                icon: 'bi-database',
                href: route('settings.database'),
                tone: 'Error',
            },
            {
                label: 'Fornecedores',
                icon: 'bi-truck',
                href: route('settings.suppliers'),
                tone: 'Info',
            },
            {
                label: 'AnyDesck',
                icon: 'bi-pc-display',
                href: route('settings.anydesck'),
                tone: 'Info',
            },
            {
                label: 'Disputa de Vendas',
                icon: 'bi-hammer',
                href: route('settings.sales-disputes'),
                tone: 'Success',
            },
            {
                label: 'Avisos',
                icon: 'bi-megaphone',
                href: route('settings.notices'),
                tone: 'Dark',
            },
        );
    }

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex flex-col gap-1">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
                        Farrammentas
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-300">
                        Gerencie menus e relatorios.
                    </p>
                </div>
            }
        >
            <Head title="Farrammentas" />
            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2">
                        {options.map((opt) => (
                            <a
                                key={opt.label}
                                href={opt.href ?? '#'}
                                className="flex items-center justify-between rounded-3xl border border-gray-200 bg-white px-4 py-4 shadow-sm transition hover:border-indigo-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900"
                            >
                                <div className="flex items-center gap-3 min-w-0">
                                    <i className={`bi ${opt.icon} text-xl text-indigo-500`} aria-hidden="true"></i>
                                    <span className="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {opt.label}
                                    </span>
                                    <span
                                        className={`shrink-0 rounded-full border px-2.5 py-0.5 text-xs font-semibold leading-5 ${TONE_CLASS_MAP[opt.tone] ?? TONE_CLASS_MAP.Dark}`}
                                    >
                                        {opt.tone}
                                    </span>
                                </div>
                                <span className="text-xs font-medium text-indigo-600 dark:text-indigo-300">
                                    {opt.href ? 'Abrir' : 'Em breve'}
                                </span>
                            </a>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

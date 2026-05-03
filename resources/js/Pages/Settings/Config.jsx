import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

const TONE_CLASS_MAP = {
    Dark: {
        card: 'border-slate-800 bg-slate-800 text-white hover:border-slate-700 hover:bg-slate-700 dark:border-slate-800 dark:bg-slate-800 dark:text-white',
        icon: 'text-white',
        label: 'text-white',
        action: 'text-white/90',
    },
    Primary: {
        card: 'border-blue-500 bg-blue-500 text-white hover:border-blue-600 hover:bg-blue-600 dark:border-blue-500 dark:bg-blue-500 dark:text-white',
        icon: 'text-white',
        label: 'text-white',
        action: 'text-white/90',
    },
    Info: {
        card: 'border-cyan-500 bg-cyan-500 text-white hover:border-cyan-600 hover:bg-cyan-600 dark:border-cyan-500 dark:bg-cyan-500 dark:text-white',
        icon: 'text-white',
        label: 'text-white',
        action: 'text-white/90',
    },
    Warning: {
        card: 'border-amber-400 bg-amber-400 text-slate-950 hover:border-amber-500 hover:bg-amber-500 dark:border-amber-400 dark:bg-amber-400 dark:text-slate-950',
        icon: 'text-slate-950',
        label: 'text-slate-950',
        action: 'text-slate-900/80',
    },
    Success: {
        card: 'border-green-500 bg-green-500 text-white hover:border-green-600 hover:bg-green-600 dark:border-green-500 dark:bg-green-500 dark:text-white',
        icon: 'text-white',
        label: 'text-white',
        action: 'text-white/90',
    },
    Error: {
        card: 'border-red-500 bg-red-500 text-white hover:border-red-600 hover:bg-red-600 dark:border-red-500 dark:bg-red-500 dark:text-white',
        icon: 'text-white',
        label: 'text-white',
        action: 'text-white/90',
    },
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
            label: 'Relatorio Gastos',
            icon: 'bi-receipt',
            href: route('reports.gastos'),
            tone: 'Success',
        },
        {
            label: 'Configuracao do Discarte',
            icon: 'bi-percent',
            href: route('settings.discard-config'),
            tone: 'Warning',
        },
        {
            label: 'Controle de Pagamentos',
            icon: 'bi-cash-coin',
            href: route('settings.payment-control'),
            tone: 'Warning',
        },
        {
            label: 'Configuracao Fiscal',
            icon: 'bi-receipt-cutoff',
            href: route('settings.fiscal'),
            tone: 'Error',
        },
        {
            label: 'NFe - Corretora de Seguros',
            icon: 'bi-file-earmark-text',
            href: route('settings.nfe'),
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
                        {options.map((opt) => {
                            const tone = TONE_CLASS_MAP[opt.tone] ?? TONE_CLASS_MAP.Dark;

                            return (
                                <a
                                    key={opt.label}
                                    href={opt.href ?? '#'}
                                    className={`flex items-center justify-between rounded-3xl border px-4 py-4 shadow-sm transition hover:shadow-md ${tone.card}`}
                                >
                                    <div className="flex items-center gap-3 min-w-0">
                                        <i className={`bi ${opt.icon} text-xl ${tone.icon}`} aria-hidden="true"></i>
                                        <span className={`truncate text-sm font-semibold ${tone.label}`}>
                                            {opt.label}
                                        </span>
                                    </div>
                                    <span className={`text-xs font-medium ${tone.action}`}>
                                        {opt.href ? 'Abrir' : 'Em breve'}
                                    </span>
                                </a>
                            );
                        })}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import {
    buildSupportTicketMenuCounters,
    getSupportTicketStatusStyle,
} from '@/Utils/supportTicketStatus';
import { Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';

const SupportTicketCounters = ({ items = [] }) => {
    if (!items.length) {
        return null;
    }

    return (
        <span className="inline-flex flex-wrap items-center gap-1">
            {items.map((item) => (
                <span
                    key={item.status}
                    className="inline-flex min-w-[20px] items-center justify-center rounded-full border px-1.5 py-0.5 text-[10px] font-bold leading-none"
                    style={getSupportTicketStatusStyle(item.status)}
                    title={`${item.label}: ${item.count}`}
                >
                    {item.count}
                </span>
            ))}
        </span>
    );
};

const MenuLabel = ({ icon, text, attention = false, trailing = null, textClassName = '' }) => (
    <span className="inline-flex flex-wrap items-center gap-2">
        <i className={`${icon} text-base`} aria-hidden="true"></i>
        <span className={textClassName}>{text}</span>
        {attention && (
            <i
                className="bi bi-exclamation-triangle-fill text-amber-500"
                aria-hidden="true"
                title="Alerta de discarte"
            ></i>
        )}
        {trailing}
    </span>
);

const formatFiscalCurrency = (value) =>
    Number(value ?? 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

const ACCESS_STORAGE_KEY = 'menuAccessConfig';
const ORDER_STORAGE_KEY = 'menuOrderConfig';
const DEFAULT_MENU_KEYS = [
    'dashboard',
    'users',
    'units',
    'products',
    'boletos',
    'cashier_close',
    'reports_control',
    'reports_cash',
    'reports_sales_today',
    'reports_sales_period',
    'reports_sales_detailed',
    'reports_lanchonete',
    'reports_comandas_aberto',
    'reports_vale',
    'reports_refeicao',
    'reports_faturar',
    'reports_adiantamentos',
    'reports_fornecedores',
    'supplier_disputes',
    'reports_gastos',
    'reports_descarte',
    'reports_descarte_consolidado',
    'reports_hoje',
    'discard',
    'switch_unit',
    'salary_advances',
    'expenses',
    'support_tickets',
    'online_users',
    'notices',
    'settings',
    'lanchonete_terminal',
];
const MENU_ORDER_PRIORITY = [
    'dashboard',
    'products',
    'support_tickets',
    'reports_control',
    'reports_cash',
    'cashier_close',
    'lanchonete_terminal',
    'reports_hoje',
    'discard',
    'expenses',
    'boletos',
    'reports_sales_today',
    'reports_lanchonete',
    'reports_sales_period',
    'reports_descarte_consolidado',
    'reports_sales_detailed',
    'online_users',
    'settings',
];

const normalizeMenuOrder = (order, allowedKeys) => {
    const allowedSet = new Set(allowedKeys);
    const source = Array.isArray(order) ? order : [];
    const uniqueKeys = source.filter((key, index) => allowedSet.has(key) && source.indexOf(key) === index);
    const merged = [...uniqueKeys, ...allowedKeys.filter((key) => !uniqueKeys.includes(key))];
    const priorityMap = MENU_ORDER_PRIORITY.reduce((acc, key, index) => {
        acc[key] = index;
        return acc;
    }, {});

    return [...merged].sort((left, right) => {
        const leftPriority =
            priorityMap[left] !== undefined ? priorityMap[left] : 1000 + merged.indexOf(left);
        const rightPriority =
            priorityMap[right] !== undefined ? priorityMap[right] : 1000 + merged.indexOf(right);

        return leftPriority - rightPriority;
    });
};

const normalizeMenuAccessConfig = (config, allowedKeys) => {
    if (!config || typeof config !== 'object') {
        return config;
    }

    let changed = false;
    const normalized = { ...config };

    Object.entries(normalized).forEach(([role, value]) => {
        if (!Array.isArray(value)) {
            return;
        }

        const filtered = value.filter((key, index) => allowedKeys.includes(key) && value.indexOf(key) === index);
        const missingKeys = allowedKeys.filter((key) => !filtered.includes(key));

        if (missingKeys.length > 0 || filtered.length !== value.length) {
            normalized[role] = [...filtered, ...missingKeys];
            changed = true;
        }
    });

    return {
        changed,
        config: normalized,
    };
};

export default function AuthenticatedLayout({ header, headerClassName = '', children }) {
    const pageProps = usePage().props;
    const user = pageProps.auth.user;
    const applicationId = user ? Number(user.matriz?.tb28_id ?? 0) : 0;
    const isNfeApplication = applicationId === 3;
    const productCatalogMode = String(pageProps.catalogMode ?? 'all');
    const activeUnitName = pageProps.auth.unit?.name ?? 'Dashboard';
    const activeUnitId = Number(pageProps.auth.unit?.id ?? pageProps.auth.unit?.tb2_id ?? 0);
    const discardAlert = pageProps.discardAlert ?? null;
    const supportTicketsMenu = pageProps.supportTicketsMenu ?? null;
    const pendingFiscalTransmissions = pageProps.pendingFiscalTransmissions ?? {
        count: 0,
        items: [],
    };
    const effectiveRole = user ? Number(user.funcao) : null;
    const originalRole = user ? Number(user.funcao_original ?? user.funcao) : null;
    const roleLabels = {
        7: 'BOSS',
        0: 'MASTER',
        1: 'GERENTE',
        2: 'SUB-GERENTE',
        3: 'CAIXA',
        4: 'LANCHONETE',
        5: 'FUNCIONÁRIO',
        6: 'CLIENTE',
    };
    const isCashier = user && effectiveRole === 3;
    const isLanchonete = user && effectiveRole === 4;
    const isBoss = user && effectiveRole === 7;
    const isMaster = user && effectiveRole === 0;
    const isAdmin = user && [7, 0, 1].includes(effectiveRole);
    const canSeeHojeReport = user && [0, 1, 2, 3].includes(effectiveRole);
    const canSeeHojeReportResolved = isBoss ? false : canSeeHojeReport;
    const canSeeUsers = user && [0, 1].includes(effectiveRole);
    const canSeeUnits = canSeeUsers;
    const canSeeReports = canSeeUnits;
    const canSeeExpenses = user && (canSeeReports || effectiveRole === 3);
    const canAccessBoletos = user && [0, 1, 3].includes(effectiveRole);
    const canSwitchUnit = user && [7, 0, 1, 2, 3].includes(originalRole);
    const canSeeMatrices =
        user &&
        effectiveRole === 7 &&
        typeof route === 'function' &&
        route().has &&
        route().has('matrizes.index');
    const hasDiscardAttention = Boolean(discardAlert?.has_alert);
    const hasLanchoneteRoute =
        typeof route === 'function' && route().has && route().has('lanchonete.terminal');
    const hasHojeRoute =
        typeof route === 'function' && route().has && route().has('reports.hoje');
    const hasOnlineRoute =
        typeof route === 'function' && route().has && route().has('online.index');

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);
    const [menuAccessConfig, setMenuAccessConfig] = useState(null);
    const [menuOrderConfig, setMenuOrderConfig] = useState(null);
    const [onlineSummary, setOnlineSummary] = useState({
        unread_total: 0,
        unread_sender_ids: [],
    });
    const [showTransmitModal, setShowTransmitModal] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }
        try {
            const raw = window.localStorage.getItem(ACCESS_STORAGE_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                const normalizedAccess = normalizeMenuAccessConfig(parsed, DEFAULT_MENU_KEYS);
                if (normalizedAccess?.changed) {
                    window.localStorage.setItem(
                        ACCESS_STORAGE_KEY,
                        JSON.stringify(normalizedAccess.config),
                    );
                }
                setMenuAccessConfig(normalizedAccess?.config ?? parsed);
            }
        } catch (err) {
            console.error('Failed to load menuAccessConfig', err);
        }
        try {
            const rawOrder = window.localStorage.getItem(ORDER_STORAGE_KEY);
            if (rawOrder) {
                const parsedOrder = JSON.parse(rawOrder);
                const normalizedOrder = normalizeMenuOrder(parsedOrder, DEFAULT_MENU_KEYS);
                window.localStorage.setItem(ORDER_STORAGE_KEY, JSON.stringify(normalizedOrder));
                setMenuOrderConfig(normalizedOrder);
            }
        } catch (err) {
            console.error('Failed to load menuOrderConfig', err);
        }
    }, []);

    useEffect(() => {
        if (
            typeof window === 'undefined' ||
            typeof route !== 'function' ||
            !hasOnlineRoute ||
            !user ||
            ![7, 0, 1, 2, 3, 4].includes(effectiveRole)
        ) {
            return undefined;
        }

        let cancelled = false;

        const sendHeartbeat = () => {
            axios.post(route('online.heartbeat')).catch(() => {
                if (!cancelled) {
                    // Mantem a UI silenciosa para nao poluir a navegacao em caso de falha temporaria.
                }
            });
        };

        sendHeartbeat();
        const intervalId = window.setInterval(sendHeartbeat, 45000);

        return () => {
            cancelled = true;
            window.clearInterval(intervalId);
        };
    }, [effectiveRole, hasOnlineRoute, user]);

    const hasMenuAccess = useMemo(() => {
        const defaultAllow = new Set(DEFAULT_MENU_KEYS);

        return (key) => {
            if (!menuAccessConfig || effectiveRole === null) {
                return defaultAllow.has(key);
            }
            const allowed = menuAccessConfig[effectiveRole];
            if (!allowed) {
                return defaultAllow.has(key);
            }
            return Array.isArray(allowed) ? allowed.includes(key) : defaultAllow.has(key);
        };
    }, [menuAccessConfig, effectiveRole]);
    const canSeeOnline =
        user &&
        [7, 0, 1, 2, 3, 4].includes(effectiveRole) &&
        hasOnlineRoute &&
        hasMenuAccess('online_users');
    const canManageProducts = !isBoss && hasMenuAccess('products');
    const canManageDiscard = !isBoss && hasMenuAccess('discard');
    const canAccessNfeLaunches = isNfeApplication && user && [7, 0, 1, 2].includes(effectiveRole);
    const canAccessNfeInsuranceProducts = isNfeApplication && user && [7, 0, 1, 2].includes(effectiveRole);
    const dashboardRouteName = isNfeApplication ? 'nfe' : 'dashboard';
    const dashboardMenuLabel = isNfeApplication ? 'NFe' : activeUnitName;
    const unreadOnlineTotal = Number(onlineSummary?.unread_total ?? 0);
    const supportTicketCounters = useMemo(
        () => buildSupportTicketMenuCounters(supportTicketsMenu),
        [supportTicketsMenu],
    );
    const pendingFiscalTransmissionCount = Number(pendingFiscalTransmissions?.count ?? 0);
    const pendingFiscalTransmissionItems = Array.isArray(pendingFiscalTransmissions?.items)
        ? pendingFiscalTransmissions.items
        : [];
    const fiscalSettingsShortcutUrl =
        pendingFiscalTransmissionItems[0]?.settings_url
        ?? (isAdmin ? route(isNfeApplication ? 'settings.nfe' : 'settings.fiscal') : null);

    useEffect(() => {
        if (
            typeof window === 'undefined' ||
            typeof route !== 'function' ||
            !canSeeOnline
        ) {
            return undefined;
        }

        let cancelled = false;

        const loadOnlineSummary = () => {
            axios.get(route('online.summary')).then((response) => {
                if (cancelled) {
                    return;
                }

                const data = response?.data ?? {};
                setOnlineSummary({
                    unread_total: Number(data.unread_total ?? 0),
                    unread_sender_ids: Array.isArray(data.unread_sender_ids)
                        ? data.unread_sender_ids.map((value) => Number(value))
                        : [],
                });
            }).catch(() => {
                if (!cancelled) {
                    // Mantem o contador atual para evitar flicker em falhas temporarias.
                }
            });
        };

        loadOnlineSummary();
        const intervalId = window.setInterval(loadOnlineSummary, 60000);

        return () => {
            cancelled = true;
            window.clearInterval(intervalId);
        };
    }, [canSeeOnline]);

    const orderMap = useMemo(() => {
        if (!menuOrderConfig || !Array.isArray(menuOrderConfig)) {
            return {};
        }
        return menuOrderConfig.reduce((acc, key, idx) => {
            acc[key] = idx;
            return acc;
        }, {});
    }, [menuOrderConfig]);

    const sortMenu = (items) =>
        items
            .map((item, idx) => ({
                ...item,
                order:
                    orderMap[item.key] ??
                    (item.key === 'reports_hoje' && orderMap.discard !== undefined
                        ? orderMap.discard - 0.5
                        : 1000 + idx),
            }))
            .sort((a, b) => a.order - b.order);

    const mainMenuItems = sortMenu(
        [
            {
                key: 'matrizes',
                visible: !isNfeApplication && canSeeMatrices,
                node: (
                    <NavLink
                        href={route('matrizes.index')}
                        active={route().current('matrizes.*')}
                    >
                        <MenuLabel icon="bi bi-buildings" text="Matrizes" />
                    </NavLink>
                ),
            },
            {
                key: 'dashboard',
                visible: true,
                node: (
                    <NavLink
                        href={route(dashboardRouteName)}
                        active={route().current(dashboardRouteName)}
                    >
                        <MenuLabel icon="bi bi-speedometer2" text={dashboardMenuLabel} />
                    </NavLink>
                ),
            },
            {
                key: 'products',
                visible: !isNfeApplication && canManageProducts,
                node: (
                    <NavLink
                        href={route('products.index')}
                        active={route().current('products.*') && productCatalogMode !== 'services'}
                    >
                        <MenuLabel icon="bi bi-box-seam" text="Produtos" />
                    </NavLink>
                ),
            },
            {
                key: 'nfe_insurance_products',
                visible: canAccessNfeInsuranceProducts,
                node: (
                    <NavLink
                        href={route('nfe.insurance-products.index', activeUnitId > 0 ? { unit_id: activeUnitId } : {})}
                        active={route().current('nfe.insurance-products.*')}
                    >
                        <MenuLabel icon="bi bi-shield-check" text="Produtos Seguro" />
                    </NavLink>
                ),
            },
            {
                key: 'nfe_launches',
                visible: canAccessNfeLaunches,
                node: (
                    <NavLink
                        href={route('nfe.launches.index', activeUnitId > 0 ? { unit_id: activeUnitId } : {})}
                        active={route().current('nfe.launches.*')}
                    >
                        <MenuLabel icon="bi bi-journal-check" text="Lancamentos" />
                    </NavLink>
                ),
            },
            {
                key: 'nfe_config',
                visible: isNfeApplication && isAdmin,
                node: (
                    <NavLink
                        href={route('settings.fiscal', activeUnitId > 0 ? { unit_id: activeUnitId } : {})}
                        active={route().current('settings.fiscal')}
                    >
                        <MenuLabel icon="bi bi-gear" text="Configuracao" />
                    </NavLink>
                ),
            },
            {
                key: 'support_tickets',
                visible: !isNfeApplication && user && hasMenuAccess('support_tickets'),
                node: (
                    <NavLink
                        href={route('support.tickets.index')}
                        active={route().current('support.tickets.*')}
                    >
                        <MenuLabel
                            icon="bi bi-camera-video"
                            text="Chamados"
                            trailing={<SupportTicketCounters items={supportTicketCounters} />}
                        />
                    </NavLink>
                ),
            },
            {
                key: 'reports_control',
                visible: !isNfeApplication && isAdmin && hasMenuAccess('reports_control'),
                node: (
                    <NavLink
                        href={route('reports.control')}
                        active={route().current('reports.control')}
                    >
                        <MenuLabel icon="bi bi-graph-up-arrow" text="Controle" />
                    </NavLink>
                ),
            },
            {
                key: 'reports_cash',
                visible: !isNfeApplication && canSeeReports && hasMenuAccess('reports_cash'),
                node: (
                    <NavLink
                        href={route('reports.cash.closure')}
                        active={route().current('reports.cash.closure')}
                    >
                        <MenuLabel
                            icon="bi bi-clipboard-data"
                            text="Fech. de CAIXA"
                            attention={hasDiscardAttention}
                        />
                    </NavLink>
                ),
            },
            {
                key: 'cashier_close',
                visible: !isNfeApplication && isCashier && hasMenuAccess('cashier_close'),
                node: (
                    <NavLink
                        href={route('cashier.close')}
                        active={route().current('cashier.close')}
                    >
                        <MenuLabel icon="bi bi-cash-stack" text="Fechar CX" />
                    </NavLink>
                ),
            },
            {
                key: 'lanchonete_terminal',
                visible: !isNfeApplication && isLanchonete && hasMenuAccess('lanchonete_terminal') && hasLanchoneteRoute,
                node: (
                    <NavLink
                        href={hasLanchoneteRoute ? route('lanchonete.terminal') : '#'}
                        active={hasLanchoneteRoute ? route().current('lanchonete.terminal') : false}
                    >
                        <MenuLabel icon="bi bi-egg-fried" text="Lanchonete" />
                    </NavLink>
                ),
            },
        ].filter((item) => item.visible)
    );

    const dropdownMenuItems = sortMenu(
        [
            {
                key: 'reports_hoje',
                visible: !isNfeApplication && canSeeHojeReportResolved && hasHojeRoute && hasMenuAccess('reports_hoje'),
                node: (
                    <Dropdown.Link href={route('reports.hoje')}>
                        <MenuLabel icon="bi bi-receipt-cutoff" text="Hoje" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'discard',
                visible: !isNfeApplication && canManageDiscard,
                node: (
                    <Dropdown.Link href={route('products.discard')}>
                        <MenuLabel icon="bi bi-recycle" text="Descarte" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'expenses',
                visible: !isNfeApplication && canSeeExpenses && hasMenuAccess('expenses'),
                node: (
                    <Dropdown.Link href={route('expenses.index')}>
                        <MenuLabel icon="bi bi-receipt" text="Gastos" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'boletos',
                visible: !isNfeApplication && canAccessBoletos && hasMenuAccess('boletos'),
                node: (
                    <Dropdown.Link href={route('boletos.index')}>
                        <MenuLabel icon="bi bi-card-text" text="Boletos" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'settings_nfe',
                visible: isAdmin,
                node: (
                    <Dropdown.Link href={route('settings.nfe', activeUnitId > 0 ? { unit_id: activeUnitId } : {})}>
                        <MenuLabel icon="bi bi-file-earmark-text" text={isNfeApplication ? 'Monitor NFe' : 'NFe'} />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'reports_sales_today',
                visible: !isNfeApplication && canSeeReports && hasMenuAccess('reports_sales_today'),
                node: (
                    <Dropdown.Link href={route('reports.sales.today')}>
                        <MenuLabel icon="bi bi-calendar-day" text="Vendas hoje" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'reports_lanchonete',
                visible: !isNfeApplication && canSeeReports && hasMenuAccess('reports_lanchonete'),
                node: (
                    <Dropdown.Link href={route('reports.lanchonete')}>
                        <MenuLabel icon="bi bi-cup-hot" text="Relatório Lanchonete" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'reports_sales_period',
                visible: !isNfeApplication && canSeeReports && hasMenuAccess('reports_sales_period'),
                node: (
                    <Dropdown.Link href={route('reports.sales.period')}>
                        <MenuLabel icon="bi bi-calendar-range" text="Vendas periodo" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'reports_descarte_consolidado',
                visible: !isNfeApplication && canSeeReports && hasMenuAccess('reports_descarte_consolidado'),
                node: (
                    <Dropdown.Link href={route('reports.descarte.consolidado')}>
                        <MenuLabel icon="bi bi-bar-chart-line" text="Discarte Consolidado" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'reports_sales_detailed',
                visible: !isNfeApplication && canSeeReports && hasMenuAccess('reports_sales_detailed'),
                node: (
                    <Dropdown.Link href={route('reports.sales.detailed')}>
                        <MenuLabel icon="bi bi-card-checklist" text="Detalhado" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'online_users',
                visible: !isNfeApplication && canSeeOnline,
                node: (
                    <Dropdown.Link href={route('online.index')}>
                        <MenuLabel icon="bi bi-broadcast-pin" text="On-Line" />
                    </Dropdown.Link>
                ),
            },
            {
                key: 'fiscal_transmit',
                visible: isAdmin,
                node: (
                    <button
                        type="button"
                        onClick={() => setShowTransmitModal(true)}
                        className="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                    >
                        <MenuLabel
                            icon="bi bi-send-check"
                            text={`Transmitir (${pendingFiscalTransmissionCount})`}
                            trailing={
                                pendingFiscalTransmissionCount > 0 ? (
                                    <span className="inline-flex min-w-[22px] items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold leading-none text-emerald-700">
                                        {pendingFiscalTransmissionCount}
                                    </span>
                                ) : null
                            }
                            textClassName={pendingFiscalTransmissionCount > 0 ? 'font-bold text-emerald-700' : ''}
                        />
                    </button>
                ),
            },
            {
                key: 'settings',
                visible: !isNfeApplication && isAdmin && hasMenuAccess('settings'),
                node: (
                    <Dropdown.Link href={route('settings.config')}>
                        <MenuLabel
                            icon="bi bi-gear"
                            text="Ferramentas"
                            textClassName="font-bold"
                        />
                    </Dropdown.Link>
                ),
            },
        ].filter((item) => item.visible)
    );

    const handleLogout = () => {
        router.post(
            route('logout'),
            { _token: pageProps?.csrf_token ?? '' },
            {
                onSuccess: () => {
                    window.location.reload();
                },
            },
        );
    };

    return (
        <div className="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav className="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                                {mainMenuItems.map((item) => (
                                    <span key={item.key}>{item.node}</span>
                                ))}
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center gap-3">
                            {canSwitchUnit && (
                                <Link
                                    href={route('reports.switch-unit')}
                                    className="inline-flex items-center gap-2 rounded-full border border-sky-500 bg-sky-500 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-600 hover:border-sky-600 dark:border-sky-300 dark:bg-sky-300 dark:text-sky-950 dark:hover:border-sky-200 dark:hover:bg-sky-200"
                                >
                                    <i className="bi bi-arrow-left-right" aria-hidden="true"></i>
                                    Trocar
                                </Link>
                            )}
                            {canSeeOnline ? (
                                <Link
                                    href={route('online.index')}
                                    className="relative inline-flex items-center rounded-full border border-indigo-500 bg-indigo-500 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:border-indigo-600 hover:bg-indigo-600 dark:border-indigo-300 dark:bg-indigo-300 dark:text-indigo-950 dark:hover:border-indigo-200 dark:hover:bg-indigo-200"
                                >
                                    <span>{user.name}</span>
                                    {unreadOnlineTotal > 0 && (
                                        <span className="absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white shadow">
                                            {unreadOnlineTotal > 99 ? '99+' : unreadOnlineTotal}
                                        </span>
                                    )}
                                </Link>
                            ) : (
                                <div className="inline-flex items-center rounded-full border border-indigo-500 bg-indigo-500 px-3 py-2 text-sm font-semibold text-white shadow-sm dark:border-indigo-300 dark:bg-indigo-300 dark:text-indigo-950">
                                    {user.name}
                                </div>
                            )}
                            <div className="relative">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center gap-2 rounded-full border border-indigo-500 bg-indigo-500 px-3 py-2 text-sm font-medium leading-4 text-white shadow-sm transition duration-150 ease-in-out hover:border-indigo-600 hover:bg-indigo-600 focus:outline-none dark:border-indigo-300 dark:bg-indigo-300 dark:text-indigo-950 dark:hover:border-indigo-200 dark:hover:bg-indigo-200"
                                            >
                                                <span className="rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-semibold text-white dark:bg-white/70 dark:text-indigo-950">
                                                    {roleLabels[effectiveRole] ?? '---'}
                                                </span>

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')}>
                                            <MenuLabel icon="bi bi-person-circle" text="Perfil" />
                                        </Dropdown.Link>
                                        {dropdownMenuItems.map((item) => (
                                            <span key={item.key}>{item.node}</span>
                                        ))}
                                        <button
                                            type="button"
                                            onClick={handleLogout}
                                            className="w-full px-4 py-2 text-left text-sm font-semibold text-red-600 transition hover:text-red-700 focus:bg-red-50 dark:text-red-300 dark:hover:bg-red-500/20"
                                        >
                                            <MenuLabel icon="bi bi-box-arrow-right" text="Sair" />
                                        </button>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            showingNavigationDropdown ? 'hidden' : 'inline-flex'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown ? 'inline-flex' : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        {mainMenuItems.map((item) => (
                            <div key={item.key}>{item.node}</div>
                        ))}
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
                        <div className="px-4">
                            {canSeeOnline ? (
                                <Link
                                    href={route('online.index')}
                                    className="relative inline-flex max-w-full items-center rounded-full border border-gray-200 bg-white px-3 py-2 text-base font-medium text-gray-800 shadow-sm transition hover:border-indigo-400 hover:text-indigo-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:text-indigo-300"
                                >
                                    <span className="truncate">{user.name}</span>
                                    {unreadOnlineTotal > 0 && (
                                        <span className="absolute -right-1 -top-1 inline-flex min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white shadow">
                                            {unreadOnlineTotal > 99 ? '99+' : unreadOnlineTotal}
                                        </span>
                                    )}
                                </Link>
                            ) : (
                                <div className="text-base font-medium text-gray-800 dark:text-gray-200">
                                    {user.name}
                                </div>
                            )}
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                                <span className="ms-2 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    {roleLabels[effectiveRole] ?? '---'}
                                </span>
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <div>
                                {canSwitchUnit && (
                                    <ResponsiveNavLink
                                        href={route('reports.switch-unit')}
                                        active={route().current('reports.switch-unit')}
                                    >
                                        Trocar
                                    </ResponsiveNavLink>
                                )}
                                <ResponsiveNavLink
                                    href={route('profile.edit')}
                                    active={route().current('profile.edit')}
                                >
                                    Perfil
                                </ResponsiveNavLink>
                                {canSeeOnline && (
                                    <ResponsiveNavLink
                                        href={route('online.index')}
                                        active={route().current('online.index')}
                                    >
                                        On-Line
                                    </ResponsiveNavLink>
                                )}
                                {isAdmin && (
                                    <button
                                        type="button"
                                        onClick={() => setShowTransmitModal(true)}
                                        className="block w-full px-4 py-2 text-start text-sm font-medium leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                                    >
                                        Transmitir ({pendingFiscalTransmissionCount})
                                    </button>
                                )}
                                {dropdownMenuItems.map((item) => (
                                    <div key={item.key}>{item.node}</div>
                                ))}
                                <ResponsiveNavLink
                                    as="button"
                                    method="post"
                                    href={route('logout')}
                                >
                                    Sair
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow dark:bg-gray-800">
                    <div
                        className={`mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-2 ${headerClassName}`}
                    >
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
            <Modal
                show={showTransmitModal}
                onClose={() => setShowTransmitModal(false)}
                maxWidth="2xl"
                tone="light"
            >
                <div className="border-b border-gray-200 px-6 py-4">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">
                                Transmitir notas pendentes
                            </h3>
                            <p className="mt-1 text-sm text-gray-500">
                                Notas prontas para transmissao na SEFAZ: {pendingFiscalTransmissionCount}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            {fiscalSettingsShortcutUrl ? (
                                <Link
                                    href={fiscalSettingsShortcutUrl}
                                    className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                                >
                                    Abrir fiscal
                                </Link>
                            ) : null}
                            <button
                                type="button"
                                onClick={() => setShowTransmitModal(false)}
                                className="rounded-full border border-gray-200 px-3 py-1 text-sm font-semibold text-gray-600 hover:bg-gray-100"
                            >
                                Fechar
                            </button>
                        </div>
                    </div>
                </div>
                <div className="max-h-[70vh] overflow-y-auto px-6 py-4">
                    {pendingFiscalTransmissionItems.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500">
                            Nao existem notas prontas para transmissao neste momento.
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {pendingFiscalTransmissionItems.map((invoice) => (
                                <div
                                    key={invoice.id}
                                    className="rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm"
                                >
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="space-y-1 text-sm text-gray-700">
                                            <p className="text-base font-semibold text-gray-900">
                                                {invoice.unit_name}
                                            </p>
                                            <p>
                                                Venda #{invoice.payment_id} | {invoice.model} {invoice.serie}/{invoice.number}
                                            </p>
                                            <p>Valor: {formatFiscalCurrency(invoice.total)}</p>
                                            <p>Criada em: {invoice.created_at ?? '--'}</p>
                                            <p>Status: {invoice.status}</p>
                                            {invoice.message ? (
                                                <p className="text-xs text-gray-500">{invoice.message}</p>
                                            ) : null}
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Link
                                                href={invoice.settings_url}
                                                className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700"
                                            >
                                                Abrir fiscal
                                            </Link>
                                            <Link
                                                href={invoice.transmit_url}
                                                method="post"
                                                as="button"
                                                className="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"
                                            >
                                                Transmitir
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </Modal>
        </div>
    );
}

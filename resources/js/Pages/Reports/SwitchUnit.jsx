import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { getRoleBadgeStyle } from '@/Utils/brandBadges';
import { Head, useForm } from '@inertiajs/react';

const withAlpha = (hexColor, alpha) => {
    const normalized = String(hexColor ?? '').replace('#', '');

    if (normalized.length !== 6) {
        return hexColor;
    }

    const red = parseInt(normalized.slice(0, 2), 16);
    const green = parseInt(normalized.slice(2, 4), 16);
    const blue = parseInt(normalized.slice(4, 6), 16);

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
};

const getRoleOptionStyle = (item, selected) => {
    const roleStyle = getRoleBadgeStyle(item.label ?? item.name ?? '');

    if (selected) {
        return {
            backgroundColor: roleStyle.backgroundColor,
            borderColor: roleStyle.borderColor,
            color: roleStyle.color,
        };
    }

    return {
        backgroundColor: withAlpha(roleStyle.backgroundColor, 0.12),
        borderColor: withAlpha(roleStyle.borderColor, 0.35),
        color: roleStyle.borderColor,
    };
};

export default function SwitchUnit({
    units = [],
    unitGroups = [],
    roles = [],
    currentUnitId,
    currentRole,
    currentRoleLabel,
    originalRoleLabel,
}) {
    const { data, setData, post, processing } = useForm({
        unit_id: currentUnitId ?? units[0]?.id ?? null,
        role: currentRole ?? roles[0]?.value ?? null,
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('reports.switch-unit.update'));
    };

    const selectedUnitName = units.find((unit) => unit.id === Number(data.unit_id))?.name ?? '---';
    const selectedRoleLabel = roles.find((role) => role.value === Number(data.role))?.label ?? currentRoleLabel ?? '---';

    const renderUnitGrid = (items, emptyMessage, offset = 0) => (
        items.length ? (
            <div className="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5">
                {items.map((unit, index) =>
                    renderOption(unit, index + offset, Number(data.unit_id) === unit.id, (value) =>
                        setData('unit_id', value),
                    ),
                )}
            </div>
        ) : (
            <p className="mt-2 rounded-xl border border-dashed border-gray-300 px-3 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-300">
                {emptyMessage}
            </p>
        )
    );

    const renderUnitGroupCard = (group, groupIndex) => {
        const matrixUnit = group.matrixUnit ? [group.matrixUnit] : [];
        const branches = Array.isArray(group.branches) ? group.branches : [];
        const colorOffset = groupIndex * 5;

        return (
            <div
                key={group.key ?? `${group.matrix?.id ?? 'sem-matriz'}-${groupIndex}`}
                className="rounded-xl bg-white p-4 shadow dark:bg-gray-800"
            >
                <div className="flex items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-gray-700">
                    <h3 className="truncate text-base font-semibold text-gray-900 dark:text-gray-100">
                        {group.matrix?.name ?? 'Matriz nao identificada'}
                    </h3>
                    <span className="shrink-0 text-xs font-medium text-gray-500 dark:text-gray-300">
                        {matrixUnit.length + branches.length} unidade(s)
                    </span>
                </div>

                {renderUnitGrid([...matrixUnit, ...branches], 'Nenhuma unidade vinculada a esta matriz.', colorOffset)}
            </div>
        );
    };

    const renderOption = (item, index, selected, onSelect, valueKey = 'id', variant = 'unit') => {
        const isCurrent = item.active;
        const isRole = variant === 'role';
        const optionStyle = isRole ? getRoleOptionStyle(item, selected) : undefined;

        return (
            <button
                key={item[valueKey]}
                type="button"
                onClick={() => onSelect(item[valueKey])}
                style={optionStyle}
                className={`relative flex min-h-[34px] items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-left text-xs font-semibold shadow-sm transition ${
                    isRole
                        ? selected
                            ? 'ring-2 ring-offset-1 ring-slate-200 dark:ring-slate-700'
                            : 'hover:brightness-95 dark:hover:brightness-110'
                        : selected
                        ? 'border-slate-900 bg-slate-50 text-slate-900 ring-2 ring-slate-200 dark:border-slate-200 dark:bg-slate-800 dark:text-slate-50 dark:ring-slate-700'
                        : 'border-gray-200 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:border-slate-400 dark:hover:bg-gray-800'
                }`}
            >
                <i
                    className={`bi bi-arrow-left-right text-xs ${
                        isRole
                            ? selected
                                ? 'text-white'
                                : 'opacity-80'
                            : selected
                                ? 'text-slate-700 dark:text-slate-200'
                                : 'text-slate-500 dark:text-slate-400'
                    }`}
                    aria-hidden="true"
                ></i>
                <span className="block flex-1 truncate pr-10 leading-4">{item.name ?? item.label}</span>
                {isCurrent && (
                    <span
                        className={`absolute right-1.5 top-1/2 -translate-y-1/2 rounded-full px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide ${
                            isRole && selected
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-100'
                        }`}
                    >
                        Atual
                    </span>
                )}
            </button>
        );
    };

    const headerContent = (
        <div>
            <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Trocar
            </h2>
            <p className="text-sm text-gray-500 dark:text-gray-300">
                Unidade selecionada: {selectedUnitName} | Funcao selecionada: {selectedRoleLabel} | Funcao de origem: {originalRoleLabel ?? '---'}
            </p>
        </div>
    );

    return (
        <AuthenticatedLayout header={headerContent}>
            <Head title="Trocar" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-4">
                        <div className="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                                Matrizes e Filiais
                            </h3>
                            <div className="mt-3 space-y-3">
                                {unitGroups.length ? (
                                    unitGroups.map((group, index) => renderUnitGroupCard(group, index))
                                ) : (
                                    <p className="rounded-xl border border-dashed border-gray-300 px-3 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-300">
                                        Nenhuma unidade disponivel para troca.
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                                Trocar Funcao
                            </h3>
                            <div className="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5">
                                {roles.map((role, index) =>
                                    renderOption(
                                        role,
                                        index,
                                        Number(data.role) === role.value,
                                        (value) => setData('role', value),
                                        'value',
                                        'role',
                                    ),
                                )}
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                Atualizar sessao
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

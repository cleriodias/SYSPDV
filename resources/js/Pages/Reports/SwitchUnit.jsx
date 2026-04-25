import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

const COLOR_CYCLE = [
    'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-100',
    'border-slate-400 bg-slate-100 text-slate-900 dark:border-slate-500/40 dark:bg-slate-500/10 dark:text-slate-100',
    'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-100',
    'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-100',
    'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-100',
    'border-violet-300 bg-violet-50 text-violet-800 dark:border-violet-500/40 dark:bg-violet-500/10 dark:text-violet-100',
    'border-cyan-300 bg-cyan-50 text-cyan-800 dark:border-cyan-500/40 dark:bg-cyan-500/10 dark:text-cyan-100',
];

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

    const renderOption = (item, index, selected, onSelect, valueKey = 'id') => {
        const color = COLOR_CYCLE[index % COLOR_CYCLE.length];
        const isCurrent = item.active;

        return (
            <button
                key={item[valueKey]}
                type="button"
                onClick={() => onSelect(item[valueKey])}
                className={`relative rounded-xl border px-3 py-2 text-left text-sm font-semibold shadow-sm transition ${
                    selected
                        ? `${color} ring-2 ring-offset-2 ring-indigo-500 dark:ring-offset-gray-900`
                        : 'border-gray-200 bg-white text-gray-700 hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100'
                }`}
            >
                <span className="block pr-14 leading-5">{item.name ?? item.label}</span>
                {isCurrent && (
                    <span className="absolute right-2 top-2 rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-700 dark:bg-gray-900/70 dark:text-gray-100">
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

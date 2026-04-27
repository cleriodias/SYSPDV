import { router } from '@inertiajs/react';
import { useState } from 'react';

const BADGE_TONE_CLASS_MAP = {
    Default: 'border-slate-200 bg-white text-slate-700',
    Primary: 'border-blue-500 bg-blue-500 text-white',
    Secondary: 'border-fuchsia-500 bg-fuchsia-500 text-white',
    Info: 'border-cyan-500 bg-cyan-500 text-white',
    Success: 'border-green-500 bg-green-500 text-white',
    Warning: 'border-orange-500 bg-orange-500 text-white',
    Error: 'border-red-500 bg-red-500 text-white',
    Dark: 'border-slate-800 bg-slate-800 text-white',
    Light: 'border-slate-200 bg-white text-slate-900',
};

const ROLE_TONE_MAP = {
    BOSS: 'Warning',
    MASTER: 'Success',
    GERENTE: 'Primary',
    'SUB-GERENTE': 'Secondary',
    CAIXA: 'Info',
    LANCHONETE: 'Warning',
    FUNCIONARIO: 'Dark',
    CLIENTE: 'Light',
};

const labelTone = (label) => ROLE_TONE_MAP[label] ?? 'Dark';

const badgeClassName = (tone) =>
    `inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] ${
        BADGE_TONE_CLASS_MAP[tone] ?? BADGE_TONE_CLASS_MAP.Default
    }`;

const statusBadgeClassName = (kind) => {
    if (kind === 'current') {
        return badgeClassName('Light');
    }

    if (kind === 'inactive') {
        return badgeClassName('Error');
    }

    if (kind === 'blocked') {
        return badgeClassName('Warning');
    }

    return badgeClassName('Default');
};

const normalizeNumber = (value, fallback = 0) => {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
};

function SwitchToggle({ selected = false, disabled = false }) {
    return (
        <span
            className={`relative inline-flex h-7 w-12 shrink-0 items-center rounded-full border transition ${
                selected
                    ? 'border-green-600 bg-green-600'
                    : 'border-slate-300 bg-slate-200'
            } ${disabled ? 'opacity-60' : ''}`}
            aria-hidden="true"
        >
            <span
                className={`absolute top-[3px] h-5 w-5 rounded-full bg-white shadow-sm transition ${
                    selected ? 'translate-x-6' : 'translate-x-1'
                }`}
            />
        </span>
    );
}

function OptionButton({
    item,
    selected,
    disabled = false,
    onClick,
    busy = false,
    type = 'unit',
}) {
    const label = item.name ?? item.label ?? '---';
    const showInactive = type === 'unit' && Number(item.status ?? 1) !== 1;
    const showBlocked = type === 'unit' && Number(item.status ?? 1) === 1 && item.loginEnabled === false;

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            className={`flex w-full items-center gap-3 rounded-2xl border px-3 py-3 text-left transition ${
                selected
                    ? 'border-green-200 bg-white shadow-sm'
                    : 'border-transparent bg-transparent hover:border-slate-200 hover:bg-white/70'
            } ${disabled ? 'cursor-not-allowed opacity-70' : ''}`}
        >
            <SwitchToggle selected={selected} disabled={disabled} />

            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="truncate text-base font-semibold uppercase tracking-[0.02em] text-slate-900">
                        {label}
                    </span>

                    {item.active && <span className={statusBadgeClassName('current')}>Atual</span>}
                    {showInactive && <span className={statusBadgeClassName('inactive')}>Inativa</span>}
                    {showBlocked && <span className={statusBadgeClassName('blocked')}>Bloq.</span>}
                    {busy && <span className={statusBadgeClassName('default')}>Atualizando</span>}
                </div>

                {type === 'unit' && item.matrixName && item.matrixName !== label ? (
                    <p className="mt-1 truncate text-xs font-medium uppercase tracking-[0.12em] text-slate-500">
                        {item.matrixName}
                    </p>
                ) : null}
            </div>
        </button>
    );
}

export default function ProfileSwitchPanel({
    units = [],
    unitGroups = [],
    roles = [],
    currentUnitId,
    currentMatrixUnitId,
    initialSelectedUnitId,
    currentSessionUnitLabel,
    currentRole,
    currentRoleLabel,
    originalRoleLabel,
    initialRole = null,
    title = null,
    className = '',
}) {
    const initialUnitId = normalizeNumber(initialSelectedUnitId, normalizeNumber(units[0]?.id));
    const initialOpenMatrixUnitId = normalizeNumber(currentMatrixUnitId, normalizeNumber(units[0]?.id));

    const [selection, setSelection] = useState({
        unitId: initialUnitId > 0 ? initialUnitId : null,
        openMatrixUnitId: initialOpenMatrixUnitId > 0 ? initialOpenMatrixUnitId : null,
        role: initialRole === null ? null : normalizeNumber(initialRole),
    });
    const [submittingKey, setSubmittingKey] = useState(null);
    const canUseBossRole = roles.some((role) => normalizeNumber(role.value) === 7);
    const bossUnit = units.find((unit) => unit?.bossOnly) ?? null;
    const openUnitGroup = unitGroups.find(
        (group) => normalizeNumber(group?.matrixUnit?.id) === normalizeNumber(selection.openMatrixUnitId),
    );
    const openBranches = Array.isArray(openUnitGroup?.branches) ? openUnitGroup.branches : [];
    const selectedUnit = selection.unitId === null
        ? null
        : (units.find((unit) => normalizeNumber(unit.id) === normalizeNumber(selection.unitId))
            ?? openBranches.find((branch) => normalizeNumber(branch.id) === normalizeNumber(selection.unitId))
            ?? null);
    const selectedUnitRequiresBoss = Boolean(selectedUnit?.bossOnly);
    const selectedRoleIsBoss = normalizeNumber(selection.role, -1) === 7;

    const submitSelection = (nextUnitId, nextRole, key) => {
        if (!nextUnitId || nextRole === null || Number.isNaN(nextRole)) {
            return;
        }

        if (
            normalizeNumber(nextUnitId) === normalizeNumber(currentUnitId)
            && normalizeNumber(nextRole) === normalizeNumber(currentRole)
        ) {
            return;
        }

        setSubmittingKey(key);

        router.post(
            route('reports.switch-unit.update'),
            {
                unit_id: nextUnitId,
                role: nextRole,
            },
            {
                preserveScroll: true,
                onFinish: () => setSubmittingKey(null),
            },
        );
    };

    const handleUnitSelect = (unit) => {
        if (!unit?.selectable || submittingKey) {
            return;
        }

        const nextUnitId = normalizeNumber(unit.id);
        const nextRole = unit.bossOnly
            ? 7
            : (selection.role === null || normalizeNumber(selection.role) === 7
                ? null
                : normalizeNumber(selection.role));

        setSelection((currentSelection) => ({
            ...currentSelection,
            unitId: nextUnitId,
            openMatrixUnitId: nextUnitId,
            role: nextRole,
        }));

        if (nextRole !== null) {
            submitSelection(nextUnitId, nextRole, `unit-${nextUnitId}`);
        }
    };

    const handleBranchSelect = (branch) => {
        if (!branch?.selectable || submittingKey) {
            return;
        }

        const nextUnitId = normalizeNumber(branch.id);
        const nextRole = selection.role === null || normalizeNumber(selection.role) === 7
            ? null
            : normalizeNumber(selection.role);

        setSelection((currentSelection) => ({
            ...currentSelection,
            unitId: nextUnitId,
            openMatrixUnitId: normalizeNumber(branch.matrixUnitId ?? branch.matrixId),
            role: nextRole,
        }));

        if (nextRole !== null) {
            submitSelection(nextUnitId, nextRole, `branch-${nextUnitId}`);
        }
    };

    const handleRoleSelect = (role) => {
        if (!role || submittingKey) {
            return;
        }

        const nextRole = normalizeNumber(role.value);
        const nextUnitId = nextRole === 7
            ? normalizeNumber(bossUnit?.id)
            : normalizeNumber(selection.unitId, initialUnitId);

        if (nextRole === 7 && (!bossUnit || !bossUnit.selectable)) {
            return;
        }

        setSelection((currentSelection) => ({
            ...currentSelection,
            unitId: nextUnitId > 0 ? nextUnitId : currentSelection.unitId,
            openMatrixUnitId: nextRole === 7
                ? normalizeNumber(bossUnit?.id, currentSelection.openMatrixUnitId)
                : currentSelection.openMatrixUnitId,
            role: nextRole,
        }));

        if (nextUnitId > 0) {
            submitSelection(nextUnitId, nextRole, `role-${nextRole}`);
        }
    };

    return (
        <section className={`overflow-hidden rounded-[28px] border border-slate-200 bg-slate-50/90 shadow-sm ${className}`.trim()}>
            <div className="px-5 py-5 sm:px-7 sm:py-6">
                <h4 className="text-2xl font-semibold text-slate-900">
                    {title ?? `Troca rapida do perfil ${originalRoleLabel ?? '---'}`}
                </h4>

                <div className="mt-5 flex flex-wrap items-center gap-2">
                    <span className="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">
                        Sessao atual
                    </span>
                    <span className={badgeClassName('Info')}>
                        {currentSessionUnitLabel ?? 'DASH'}
                    </span>
                    <span className={badgeClassName(labelTone(currentRoleLabel))}>
                        {currentRoleLabel ?? '---'}
                    </span>
                </div>

                <div className="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                    <div>

                        <div className="mt-5 grid gap-2 sm:grid-cols-2">
                            {units.map((unit) => {
                                const unitId = normalizeNumber(unit.id);

                                return (
                                    <OptionButton
                                        key={unitId}
                                        item={unit}
                                        type="unit"
                                        selected={normalizeNumber(selection.unitId) === unitId}
                                        disabled={
                                            !unit.selectable
                                            || Boolean(submittingKey)
                                            || (unit.bossOnly && !canUseBossRole)
                                            || (selectedRoleIsBoss && !unit.bossOnly)
                                        }
                                        busy={submittingKey === `unit-${unitId}`}
                                        onClick={() => handleUnitSelect(unit)}
                                    />
                                );
                            })}
                        </div>

                        {selection.openMatrixUnitId !== null && (
                            <div className="mt-6">
                                <h4 className="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                                    Filiais {openUnitGroup?.matrix?.name ? `de ${openUnitGroup.matrix.name}` : ''}
                                </h4>

                                {openBranches.length ? (
                                    <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                        {openBranches.map((branch) => {
                                            const branchId = normalizeNumber(branch.id);

                                            return (
                                                <OptionButton
                                                    key={branchId}
                                                    item={branch}
                                                    type="unit"
                                                    selected={selection.unitId === branchId}
                                                    disabled={
                                                        !branch.selectable
                                                        || Boolean(submittingKey)
                                                        || selectedRoleIsBoss
                                                    }
                                                    busy={submittingKey === `branch-${branchId}`}
                                                    onClick={() => handleBranchSelect(branch)}
                                                />
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <p className="mt-3 rounded-2xl border border-dashed border-slate-200 bg-white/60 px-4 py-3 text-sm text-slate-500">
                                        Nenhuma filial vinculada a esta matriz.
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    <div>

                        <div className="mt-5 grid gap-2 sm:grid-cols-2">
                            {roles.map((role) => {
                                const roleValue = normalizeNumber(role.value);

                                return (
                                    <OptionButton
                                        key={roleValue}
                                        item={role}
                                        type="role"
                                        selected={
                                            selection.role !== null
                                            && normalizeNumber(selection.role) === roleValue
                                        }
                                        disabled={
                                            Boolean(submittingKey)
                                            || (selectedUnitRequiresBoss && roleValue !== 7)
                                            || (role.bossOnly && !bossUnit)
                                        }
                                        busy={submittingKey === `role-${roleValue}`}
                                        onClick={() => handleRoleSelect(role)}
                                    />
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

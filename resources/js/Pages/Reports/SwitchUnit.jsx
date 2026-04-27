import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ProfileSwitchPanel from '@/Components/ProfileSwitchPanel';
import { Head } from '@inertiajs/react';

export default function SwitchUnit({
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
}) {
    const headerContent = (
        <div>
            <h2 className="text-xl font-semibold leading-tight text-slate-800">
                Trocar
            </h2>
        </div>
    );

    return (
        <AuthenticatedLayout header={headerContent}>
            <Head title="Trocar" />

            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <ProfileSwitchPanel
                        units={units}
                        unitGroups={unitGroups}
                        roles={roles}
                        currentUnitId={currentUnitId}
                        currentMatrixUnitId={currentMatrixUnitId}
                        initialSelectedUnitId={initialSelectedUnitId}
                        currentSessionUnitLabel={currentSessionUnitLabel}
                        currentRole={currentRole}
                        currentRoleLabel={currentRoleLabel}
                        originalRoleLabel={originalRoleLabel}
                        initialRole={initialRole}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

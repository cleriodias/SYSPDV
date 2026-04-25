<?php

namespace App\Support;

use App\Models\Unidade;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportUnitScope
{
    public static function availableUnits(User $user, array $columns = ['tb2_id', 'tb2_nome']): Collection
    {
        if (ManagementScope::isMaster($user) || ManagementScope::isBoss($user)) {
            $matrixId = self::resolveMatrixId($user);

            if ($matrixId <= 0) {
                return collect();
            }

            return Unidade::active()
                ->where('matriz_id', $matrixId)
                ->orderBy('tb2_nome')
                ->get($columns);
        }

        return ManagementScope::managedUnits($user, $columns);
    }

    public static function resolveMatrixId(User $user): int
    {
        $matrixId = ManagementScope::scopedMatrixId($user);

        if ($matrixId > 0) {
            return $matrixId;
        }

        $primaryUnitId = (int) ($user->tb2_id ?? 0);

        if ($primaryUnitId > 0) {
            $matrixId = (int) (Unidade::query()
                ->where('tb2_id', $primaryUnitId)
                ->value('matriz_id') ?? 0);

            if ($matrixId > 0) {
                return $matrixId;
            }
        }

        return (int) ($user->units()
            ->orderBy('tb2_unidades.tb2_id')
            ->value('tb2_unidades.matriz_id') ?? 0);
    }
}

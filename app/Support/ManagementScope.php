<?php

namespace App\Support;

use App\Models\ProductDiscard;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ManagementScope
{
    public static function originalRole(?User $user): int
    {
        return (int) ($user?->funcao_original ?? $user?->funcao ?? -1);
    }

    private static function isDelegatedBoss(?User $user): bool
    {
        return $user instanceof User
            && self::originalRole($user) === 7
            && (int) $user->funcao !== 7;
    }

    private static function activeUnitId(): ?int
    {
        $unit = request()?->session()?->get('active_unit');
        $unitId = is_array($unit)
            ? ($unit['id'] ?? $unit['tb2_id'] ?? null)
            : (is_object($unit) ? ($unit->id ?? $unit->tb2_id ?? null) : null);

        return $unitId ? (int) $unitId : null;
    }

    public static function scopedMatrixId(?User $user): int
    {
        if (! $user instanceof User) {
            return 0;
        }

        if (self::isDelegatedBoss($user)) {
            $activeUnitId = self::activeUnitId();

            if ($activeUnitId) {
                return (int) (Unidade::query()
                    ->where('tb2_id', $activeUnitId)
                    ->value('matriz_id') ?? 0);
            }
        }

        return (int) ($user->matriz_id ?? 0);
    }

    public static function isBoss(?User $user): bool
    {
        return $user instanceof User && (int) $user->funcao === 7;
    }

    public static function isMaster(?User $user): bool
    {
        return $user instanceof User && (int) $user->funcao === 0;
    }

    public static function isManager(?User $user): bool
    {
        return $user instanceof User && (int) $user->funcao === 1;
    }

    public static function isSubManager(?User $user): bool
    {
        return $user instanceof User && (int) $user->funcao === 2;
    }

    public static function isManagement(?User $user): bool
    {
        return $user instanceof User && in_array((int) $user->funcao, [7, 0, 1, 2], true);
    }

    public static function isAdmin(?User $user): bool
    {
        return $user instanceof User && in_array((int) $user->funcao, [7, 0, 1], true);
    }

    public static function belongsToSameMatrix(?User $left, ?User $right): bool
    {
        if (! $left instanceof User || ! $right instanceof User) {
            return false;
        }

        if (self::isBoss($left)) {
            return true;
        }

        $leftMatrixId = self::scopedMatrixId($left);
        $rightMatrixId = (int) ($right->matriz_id ?? 0);

        return $leftMatrixId > 0 && $leftMatrixId === $rightMatrixId;
    }

    public static function managedUnitIds(User $user): Collection
    {
        if (self::isBoss($user)) {
            return Unidade::query()
                ->orderBy('tb2_id')
                ->pluck('tb2_id')
                ->map(fn ($value) => (int) $value)
                ->values();
        }

        if (self::isDelegatedBoss($user)) {
            $matrixId = self::scopedMatrixId($user);

            if ($matrixId <= 0) {
                return collect();
            }

            return Unidade::query()
                ->where('matriz_id', $matrixId)
                ->orderBy('tb2_id')
                ->pluck('tb2_id')
                ->map(fn ($value) => (int) $value)
                ->values();
        }

        $primaryId = (int) ($user->tb2_id ?? 0);
        $matrixId = self::scopedMatrixId($user);

        $unitIds = $user->units()
            ->when($matrixId > 0, fn ($query) => $query->where('tb2_unidades.matriz_id', $matrixId))
            ->pluck('tb2_unidades.tb2_id')
            ->map(fn ($value) => (int) $value);

        if ($primaryId > 0 && ! $unitIds->contains($primaryId)) {
            $unitIds->push($primaryId);
        }

        return $unitIds
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values();
    }

    public static function managedUnits(User $user, array $columns = ['tb2_id', 'tb2_nome']): Collection
    {
        if (self::isBoss($user)) {
            return Unidade::query()
                ->orderBy('tb2_nome')
                ->get($columns);
        }

        if (self::isMaster($user)) {
            $matrixId = self::scopedMatrixId($user);

            if ($matrixId <= 0) {
                return collect();
            }

            return Unidade::active()
                ->where('matriz_id', $matrixId)
                ->orderBy('tb2_nome')
                ->get($columns);
        }

        $unitIds = self::managedUnitIds($user);

        if ($unitIds->isEmpty()) {
            return collect();
        }

        return Unidade::active()
            ->whereIn('tb2_id', $unitIds)
            ->orderBy('tb2_nome')
            ->get($columns);
    }

    public static function canManageUnit(User $user, ?int $unitId): bool
    {
        if ($unitId === null || $unitId <= 0 || ! self::isManagement($user)) {
            return false;
        }

        if (self::isBoss($user)) {
            return true;
        }

        if (self::isMaster($user)) {
            $matrixId = self::scopedMatrixId($user);

            if ($matrixId <= 0) {
                return false;
            }

            return Unidade::query()
                ->where('tb2_id', $unitId)
                ->where('matriz_id', $matrixId)
                ->exists();
        }

        return self::managedUnitIds($user)->contains($unitId);
    }

    public static function targetUserUnitIds(User $user): Collection
    {
        $primaryId = (int) ($user->tb2_id ?? 0);

        $unitIds = $user->relationLoaded('units')
            ? collect($user->units)->pluck('tb2_id')->map(fn ($value) => (int) $value)
            : $user->units()->pluck('tb2_unidades.tb2_id')->map(fn ($value) => (int) $value);

        if ($primaryId > 0 && ! $unitIds->contains($primaryId)) {
            $unitIds->push($primaryId);
        }

        return $unitIds
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values();
    }

    public static function canManageUser(User $actingUser, User $targetUser): bool
    {
        if (! self::isManagement($actingUser)) {
            return false;
        }

        if (self::isBoss($actingUser)) {
            return true;
        }

        if (! self::belongsToSameMatrix($actingUser, $targetUser)) {
            return false;
        }

        if (self::isMaster($actingUser)) {
            return true;
        }

        $allowedUnitIds = self::managedUnitIds($actingUser);
        $targetUnitIds = self::targetUserUnitIds($targetUser);

        return $targetUnitIds->isNotEmpty()
            && $targetUnitIds->every(fn (int $unitId) => $allowedUnitIds->contains($unitId));
    }

    public static function discardUnitIds(ProductDiscard $discard): Collection
    {
        $discardUnitId = (int) ($discard->unit_id ?? 0);

        if ($discardUnitId > 0) {
            return collect([$discardUnitId]);
        }

        $discard->loadMissing('user.units:tb2_id,tb2_nome');

        if (! $discard->user instanceof User) {
            return collect();
        }

        return self::targetUserUnitIds($discard->user);
    }

    public static function canManageDiscard(User $actingUser, ProductDiscard $discard): bool
    {
        if (! self::isAdmin($actingUser)) {
            return false;
        }

        if (self::isMaster($actingUser)) {
            return true;
        }

        $discardUnitIds = self::discardUnitIds($discard);

        return $discardUnitIds->isNotEmpty()
            && $discardUnitIds->every(fn (int $unitId) => self::canManageUnit($actingUser, $unitId));
    }

    public static function applyManagedUserScope(Builder $query, User $user): Builder
    {
        if (self::isBoss($user)) {
            return $query;
        }

        if (self::isMaster($user)) {
            $matrixId = self::scopedMatrixId($user);

            if ($matrixId <= 0) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('users.matriz_id', $matrixId);
        }

        if (! self::isManagement($user)) {
            return $query->whereRaw('1 = 0');
        }

        $allowedUnitIds = self::managedUnitIds($user)->all();

        if (empty($allowedUnitIds)) {
            return $query->whereRaw('1 = 0');
        }

        $matrixId = self::scopedMatrixId($user);

        if ($matrixId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('users.matriz_id', $matrixId)
            ->where(function (Builder $subQuery) use ($allowedUnitIds) {
                $subQuery
                    ->whereIn('users.tb2_id', $allowedUnitIds)
                    ->orWhereHas('units', function (Builder $unitQuery) use ($allowedUnitIds) {
                        $unitQuery->whereIn('tb2_unidades.tb2_id', $allowedUnitIds);
                    });
            })
            ->where(function (Builder $subQuery) use ($allowedUnitIds) {
                $subQuery
                    ->whereNull('users.tb2_id')
                    ->orWhereIn('users.tb2_id', $allowedUnitIds);
            })
            ->whereDoesntHave('units', function (Builder $unitQuery) use ($allowedUnitIds) {
                $unitQuery->whereNotIn('tb2_unidades.tb2_id', $allowedUnitIds);
            });
    }
}

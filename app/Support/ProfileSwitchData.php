<?php

namespace App\Support;

use App\Models\Aplicacao;
use App\Models\Matriz;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProfileSwitchData
{
    private const SWITCHABLE_ORIGINAL_ROLES = [7, 0, 1, 2, 3];

    private const ROLE_OPTIONS = [
        7 => 'BOSS',
        0 => 'MASTER',
        1 => 'GERENTE',
        2 => 'SUB-GERENTE',
        3 => 'CAIXA',
        4 => 'LANCHONETE',
        5 => 'FUNCIONARIO',
        6 => 'CLIENTE',
    ];

    private const APPLICATION_ROLE_EXCLUSIONS = [
        Aplicacao::NFE => [2, 4],
    ];

    private const ROLE_HIERARCHY = [
        7,
        0,
        1,
        2,
        3,
        4,
        5,
        6,
    ];

    public static function canAccess($user): bool
    {
        return $user && in_array(self::originalRole($user), self::SWITCHABLE_ORIGINAL_ROLES, true);
    }

    public static function originalRole($user): int
    {
        return ManagementScope::originalRole($user);
    }

    public static function roleLabel(int $role): string
    {
        return self::ROLE_OPTIONS[$role] ?? '---';
    }

    public static function isBossRole(int $role): bool
    {
        return $role === 7;
    }

    public static function isBossOnlyUnit(Unidade $unit): bool
    {
        return ManagementScope::isBossUnit($unit);
    }

    public static function allowedUnits($user): Collection
    {
        if (self::originalRole($user) === 7) {
            return Unidade::query()
                ->orderBy('tb2_tipo')
                ->orderBy('tb2_nome')
                ->get(['tb2_id', 'tb2_nome', 'tb2_endereco', 'tb2_cnpj', 'tb2_tipo', 'matriz_id', 'tb2_status', 'login_liberado'])
                ->load('matriz:id,nome');
        }

        $matrixId = self::resolveUserMatrixId($user);

        if ($matrixId <= 0) {
            return collect();
        }

        return Unidade::query()
            ->where('matriz_id', $matrixId)
            ->orderBy('tb2_tipo')
            ->orderBy('tb2_nome')
            ->get(['tb2_id', 'tb2_nome', 'tb2_endereco', 'tb2_cnpj', 'tb2_tipo', 'matriz_id', 'tb2_status', 'login_liberado'])
            ->load('matriz:id,nome');
    }

    public static function canUseRole(int $originalRole, int $role): bool
    {
        if ($role === 7 && ! ManagementScope::isBossAccount(request()?->user())) {
            return false;
        }

        if (! array_key_exists($originalRole, self::ROLE_OPTIONS) || ! array_key_exists($role, self::ROLE_OPTIONS)) {
            return false;
        }

        $originalIndex = array_search($originalRole, self::ROLE_HIERARCHY, true);
        $roleIndex = array_search($role, self::ROLE_HIERARCHY, true);

        if ($originalIndex === false || $roleIndex === false) {
            return false;
        }

        return $roleIndex >= $originalIndex;
    }

    public static function canSelectUnit(Unidade $unit): bool
    {
        return (int) ($unit->tb2_status ?? 0) === 1
            && (bool) ($unit->login_liberado ?? true);
    }

    public static function isValidSelection($user, ?Unidade $unit, int $role): bool
    {
        if (! $unit || ! array_key_exists($role, self::ROLE_OPTIONS)) {
            return false;
        }

        if (! self::isRoleAllowedForApplication($role, self::resolveApplicationIdForMatrixId((int) ($unit->matriz_id ?? 0)))) {
            return false;
        }

        $isBossUnit = self::isBossOnlyUnit($unit);
        $isBossRole = self::isBossRole($role);

        if ($isBossUnit xor $isBossRole) {
            return false;
        }

        if ($isBossRole && ! ManagementScope::isBossAccount($user)) {
            return false;
        }

        return true;
    }

    public static function forRequest(Request $request): array
    {
        $user = $request->user();
        $originalRole = self::originalRole($user);
        $currentRole = (int) ($user?->funcao ?? -1);
        $applicationId = self::resolveCurrentApplicationId($request, $user);
        $currentUnitId = self::resolveCurrentActiveUnitId($request);
        $currentMatrixUnitId = self::resolveCurrentMatrixUnitId($request, $user);
        $initialSelectedUnitId = $currentUnitId > 0 ? $currentUnitId : $currentMatrixUnitId;

        $allUnits = self::allowedUnits($user)
            ->map(fn (Unidade $unit) => [
                'id' => $unit->tb2_id,
                'name' => ActiveUnitSessionData::displayName($unit),
                'type' => (string) ($unit->tb2_tipo ?? 'filial'),
                'matrixId' => (int) ($unit->matriz_id ?? 0),
                'matrixName' => trim((string) ($unit->matriz?->nome ?? '')) ?: ActiveUnitSessionData::displayName($unit),
                'status' => (int) ($unit->tb2_status ?? 0),
                'loginEnabled' => (bool) ($unit->login_liberado ?? true),
                'selectable' => self::canSelectUnit($unit),
                'bossOnly' => self::isBossOnlyUnit($unit),
                'active' => $initialSelectedUnitId === (int) $unit->tb2_id,
            ])
            ->values();

        $units = $allUnits
            ->filter(fn (array $unit) => self::isMatrixType($unit['type'] ?? null))
            ->sort(fn (array $left, array $right) => self::compareUnits($left, $right))
            ->values();

        $roles = collect(self::availableRoleOptions($applicationId))
            ->filter(fn (string $label, int $value) => self::canUseRole($originalRole, $value))
            ->map(fn (string $label, int $value) => [
                'value' => $value,
                'label' => $label,
                'bossOnly' => self::isBossRole($value),
                'active' => $currentRole === $value,
            ])
            ->values();

        return [
            'units' => $units,
            'unitGroups' => self::groupUnitsByMatrix($allUnits),
            'roles' => $roles,
            'currentUnitId' => $currentUnitId,
            'currentMatrixUnitId' => $currentMatrixUnitId,
            'initialSelectedUnitId' => $initialSelectedUnitId,
            'currentSessionUnitLabel' => trim((string) ($request->session()->get('active_unit.name') ?? '')) ?: 'DASH',
            'currentRole' => $currentRole,
            'currentRoleLabel' => self::roleLabel($currentRole),
            'originalRole' => $originalRole,
            'originalRoleLabel' => self::roleLabel($originalRole),
            'initialRole' => null,
        ];
    }

    private static function availableRoleOptions(?int $applicationId): array
    {
        return array_filter(
            self::ROLE_OPTIONS,
            fn (int $role) => self::isRoleAllowedForApplication($role, $applicationId),
            ARRAY_FILTER_USE_KEY
        );
    }

    private static function compareUnits(array $left, array $right): int
    {
        $leftPriority = [
            $left['active'] ? 0 : 1,
            $left['selectable'] ? 0 : 1,
            self::isMatrixType($left['type'] ?? null) ? 0 : 1,
            mb_strtolower((string) ($left['name'] ?? '')),
        ];

        $rightPriority = [
            $right['active'] ? 0 : 1,
            $right['selectable'] ? 0 : 1,
            self::isMatrixType($right['type'] ?? null) ? 0 : 1,
            mb_strtolower((string) ($right['name'] ?? '')),
        ];

        return $leftPriority <=> $rightPriority;
    }

    private static function isMatrixType(?string $type): bool
    {
        return mb_strtolower(trim((string) $type)) === 'matriz';
    }

    private static function resolveCurrentActiveUnitId(Request $request): int
    {
        return (int) ($request->session()->get('active_unit.id') ?? 0);
    }

    private static function resolveCurrentApplicationId(Request $request, $user): int
    {
        $sessionUnitId = self::resolveCurrentActiveUnitId($request);

        if ($sessionUnitId > 0) {
            $applicationId = self::resolveApplicationIdForUnitId($sessionUnitId);

            if ($applicationId > 0) {
                return $applicationId;
            }
        }

        return self::resolveApplicationIdForMatrixId(self::resolveUserMatrixId($user));
    }

    private static function resolveUserMatrixId($user): int
    {
        $matrixId = (int) ($user?->matriz_id ?? 0);

        if ($matrixId > 0) {
            return $matrixId;
        }

        $primaryUnitId = (int) ($user?->tb2_id ?? 0);

        if ($primaryUnitId <= 0) {
            return 0;
        }

        return (int) (Unidade::query()
            ->where('tb2_id', $primaryUnitId)
            ->value('matriz_id') ?? 0);
    }

    private static function resolveCurrentMatrixUnitId(Request $request, $user): int
    {
        $sessionUnitId = (int) ($request->session()->get('active_unit.id') ?? 0);

        if ($sessionUnitId > 0) {
            $sessionUnit = Unidade::query()
                ->select('tb2_id', 'tb2_tipo', 'matriz_id')
                ->find($sessionUnitId);

            if ($sessionUnit) {
                if (self::isMatrixType($sessionUnit->tb2_tipo ?? null)) {
                    return (int) $sessionUnit->tb2_id;
                }

                $matrixUnitId = self::resolveMatrixUnitId((int) ($sessionUnit->matriz_id ?? 0));

                if ($matrixUnitId > 0) {
                    return $matrixUnitId;
                }
            }
        }

        $primaryUnitId = (int) ($user?->tb2_id ?? 0);

        if ($primaryUnitId <= 0) {
            return 0;
        }

        $primaryUnit = Unidade::query()
            ->select('tb2_id', 'tb2_tipo', 'matriz_id')
            ->find($primaryUnitId);

        if (! $primaryUnit) {
            return 0;
        }

        if (self::isMatrixType($primaryUnit->tb2_tipo ?? null)) {
            return (int) $primaryUnit->tb2_id;
        }

        return self::resolveMatrixUnitId((int) ($primaryUnit->matriz_id ?? 0));
    }

    private static function resolveMatrixUnitId(int $matrixId): int
    {
        if ($matrixId <= 0) {
            return 0;
        }

        return (int) (Unidade::query()
            ->where('matriz_id', $matrixId)
            ->whereRaw('LOWER(TRIM(tb2_tipo)) = ?', ['matriz'])
            ->orderBy('tb2_id')
            ->value('tb2_id') ?? 0);
    }

    private static function resolveApplicationIdForUnitId(int $unitId): int
    {
        if ($unitId <= 0) {
            return 0;
        }

        $matrixId = (int) (Unidade::query()
            ->where('tb2_id', $unitId)
            ->value('matriz_id') ?? 0);

        return self::resolveApplicationIdForMatrixId($matrixId);
    }

    private static function resolveApplicationIdForMatrixId(int $matrixId): int
    {
        if ($matrixId <= 0) {
            return 0;
        }

        return (int) (Matriz::query()
            ->where('id', $matrixId)
            ->value('tb28_id') ?? 0);
    }

    private static function isRoleAllowedForApplication(int $role, ?int $applicationId): bool
    {
        return ! in_array($role, self::APPLICATION_ROLE_EXCLUSIONS[$applicationId] ?? [], true);
    }

    private static function groupUnitsByMatrix(Collection $units): Collection
    {
        return $units
            ->groupBy(fn (array $unit) => $unit['matrixId'] > 0 ? 'matrix-' . $unit['matrixId'] : 'unit-' . $unit['id'])
            ->map(function (Collection $group) {
                $first = $group->first();
                $matrixUnit = $group->first(fn (array $unit) => strcasecmp($unit['type'], 'matriz') === 0);
                $branches = $group
                    ->reject(fn (array $unit) => strcasecmp($unit['type'], 'matriz') === 0)
                    ->map(fn (array $unit) => [
                        ...$unit,
                        'matrixUnitId' => $matrixUnit['id'] ?? null,
                    ])
                    ->values()
                    ->all();

                return [
                    'key' => $first['matrixId'] > 0 ? 'matrix-' . $first['matrixId'] : 'unit-' . $first['id'],
                    'matrix' => [
                        'id' => $first['matrixId'] > 0 ? $first['matrixId'] : null,
                        'name' => $matrixUnit['matrixName'] ?? $first['matrixName'] ?? $first['name'],
                    ],
                    'matrixUnit' => $matrixUnit,
                    'branches' => $branches,
                ];
            })
            ->sortBy(fn (array $group) => mb_strtolower((string) ($group['matrix']['name'] ?? '')))
            ->values();
    }
}

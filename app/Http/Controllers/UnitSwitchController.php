<?php

namespace App\Http\Controllers;

use App\Models\Unidade;
use App\Support\ActiveUnitSessionData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitSwitchController extends Controller
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

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureCanSwitchUnit($user);
        $originalRole = $this->originalRole($user);
        $currentRole = (int) $user->funcao;
        $currentUnitId = $this->resolveCurrentActiveUnitId($request);
        $currentMatrixUnitId = $this->resolveCurrentMatrixUnitId($request, $user);
        $initialSelectedUnitId = $currentUnitId > 0 ? $currentUnitId : $currentMatrixUnitId;
        $allUnits = $this->allowedUnits($user)
            ->map(fn (Unidade $unit) => [
                'id' => $unit->tb2_id,
                'name' => ActiveUnitSessionData::displayName($unit),
                'type' => (string) ($unit->tb2_tipo ?? 'filial'),
                'matrixId' => (int) ($unit->matriz_id ?? 0),
                'matrixName' => trim((string) ($unit->matriz?->nome ?? '')) ?: ActiveUnitSessionData::displayName($unit),
                'status' => (int) ($unit->tb2_status ?? 0),
                'loginEnabled' => (bool) ($unit->login_liberado ?? true),
                'selectable' => $this->canSelectUnit($unit),
                'active' => $initialSelectedUnitId === (int) $unit->tb2_id,
            ])
            ->values();
        $units = $allUnits
            ->filter(fn (array $unit) => $this->isMatrixType($unit['type'] ?? null))
            ->sort(fn (array $left, array $right) => $this->compareUnits($left, $right))
            ->values();

        $unitGroups = $this->groupUnitsByMatrix($allUnits);

        $roles = collect(self::ROLE_OPTIONS)
            ->filter(fn (string $label, int $value) => $this->canUseRole($originalRole, $value))
            ->map(fn (string $label, int $value) => [
                'value' => $value,
                'label' => $label,
                'active' => $currentRole === $value,
            ])
            ->values();

        return Inertia::render('Reports/SwitchUnit', [
            'units' => $units,
            'unitGroups' => $unitGroups,
            'roles' => $roles,
            'currentUnitId' => $currentUnitId,
            'currentMatrixUnitId' => $currentMatrixUnitId,
            'initialSelectedUnitId' => $initialSelectedUnitId,
            'currentSessionUnitLabel' => trim((string) ($request->session()->get('active_unit.name') ?? '')) ?: 'DASH',
            'currentRole' => $currentRole,
            'currentRoleLabel' => self::ROLE_OPTIONS[$currentRole] ?? '---',
            'originalRole' => $originalRole,
            'originalRoleLabel' => self::ROLE_OPTIONS[$originalRole] ?? '---',
            'initialRole' => null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanSwitchUnit($user);
        $originalRole = $this->originalRole($user);

        $validated = $request->validate([
            'unit_id' => ['required', 'integer'],
            'role' => ['required', 'integer', 'between:0,7'],
        ]);

        $units = $this->allowedUnits($user);
        $unit = $units->firstWhere('tb2_id', (int) $validated['unit_id']);
        $role = (int) $validated['role'];

        if (
            ! $unit
            || ! array_key_exists($role, self::ROLE_OPTIONS)
            || ! $this->canUseRole($originalRole, $role)
            || ! $this->canSelectUnit($unit)
        ) {
            abort(403);
        }

        $request->session()->put('active_unit', ActiveUnitSessionData::fromUnit($unit));
        $request->session()->put('active_role', $role);

        return redirect()->route('dashboard')->with('success', 'Sessao atualizada com sucesso!');
    }

    private function allowedUnits($user)
    {
        if ($this->originalRole($user) === 7) {
            return Unidade::query()
                ->orderBy('tb2_tipo')
                ->orderBy('tb2_nome')
                ->get(['tb2_id', 'tb2_nome', 'tb2_endereco', 'tb2_cnpj', 'tb2_tipo', 'matriz_id', 'tb2_status', 'login_liberado'])
                ->load('matriz:id,nome');
        }

        $matrixId = $this->resolveUserMatrixId($user);

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

    private function ensureCanSwitchUnit($user): void
    {
        $roleOriginal = $this->originalRole($user);

        if (! $user || ! in_array($roleOriginal, self::SWITCHABLE_ORIGINAL_ROLES, true)) {
            abort(403);
        }
    }

    private function originalRole($user): int
    {
        return (int) ($user?->funcao_original ?? $user?->funcao ?? -1);
    }

    private function canUseRole(int $originalRole, int $role): bool
    {
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

    private function canSelectUnit(Unidade $unit): bool
    {
        return (int) ($unit->tb2_status ?? 0) === 1
            && (bool) ($unit->login_liberado ?? true);
    }

    private function compareUnits(array $left, array $right): int
    {
        $leftPriority = [
            $left['active'] ? 0 : 1,
            $left['selectable'] ? 0 : 1,
            $this->isMatrixType($left['type'] ?? null) ? 0 : 1,
            mb_strtolower((string) ($left['name'] ?? '')),
        ];

        $rightPriority = [
            $right['active'] ? 0 : 1,
            $right['selectable'] ? 0 : 1,
            $this->isMatrixType($right['type'] ?? null) ? 0 : 1,
            mb_strtolower((string) ($right['name'] ?? '')),
        ];

        return $leftPriority <=> $rightPriority;
    }

    private function isMatrixType(?string $type): bool
    {
        return mb_strtolower(trim((string) $type)) === 'matriz';
    }

    private function resolveCurrentActiveUnitId(Request $request): int
    {
        return (int) ($request->session()->get('active_unit.id') ?? 0);
    }

    private function resolveUserMatrixId($user): int
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

    private function resolveCurrentMatrixUnitId(Request $request, $user): int
    {
        $sessionUnitId = (int) ($request->session()->get('active_unit.id') ?? 0);

        if ($sessionUnitId > 0) {
            $sessionUnit = Unidade::query()
                ->select('tb2_id', 'tb2_tipo', 'matriz_id')
                ->find($sessionUnitId);

            if ($sessionUnit) {
                if ($this->isMatrixType($sessionUnit->tb2_tipo ?? null)) {
                    return (int) $sessionUnit->tb2_id;
                }

                $matrixUnitId = $this->resolveMatrixUnitId((int) ($sessionUnit->matriz_id ?? 0));

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

        if ($this->isMatrixType($primaryUnit->tb2_tipo ?? null)) {
            return (int) $primaryUnit->tb2_id;
        }

        return $this->resolveMatrixUnitId((int) ($primaryUnit->matriz_id ?? 0));
    }

    private function resolveMatrixUnitId(int $matrixId): int
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

    private function groupUnitsByMatrix($units)
    {
        return $units
            ->groupBy(fn (array $unit) => $unit['matrixId'] > 0 ? 'matrix-' . $unit['matrixId'] : 'unit-' . $unit['id'])
            ->map(function ($group) {
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

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

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureCanSwitchUnit($user);
        $originalRole = $this->originalRole($user);
        $currentRole = (int) $user->funcao;

        $units = $this->allowedUnits($user)
            ->map(fn (Unidade $unit) => [
                'id' => $unit->tb2_id,
                'name' => ActiveUnitSessionData::displayName($unit),
                'type' => (string) ($unit->tb2_tipo ?? 'filial'),
                'matrixId' => (int) ($unit->matriz_id ?? 0),
                'matrixName' => trim((string) ($unit->matriz?->nome ?? '')) ?: ActiveUnitSessionData::displayName($unit),
                'active' => (int) ($request->session()->get('active_unit.id')) === $unit->tb2_id,
            ])
            ->values();

        $unitGroups = $this->groupUnitsByMatrix($units);

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
            'currentUnitId' => (int) ($request->session()->get('active_unit.id') ?? $user->tb2_id ?? 0),
            'currentRole' => $currentRole,
            'currentRoleLabel' => self::ROLE_OPTIONS[$currentRole] ?? '---',
            'originalRole' => $originalRole,
            'originalRoleLabel' => self::ROLE_OPTIONS[$originalRole] ?? '---',
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
        ) {
            abort(403);
        }

        if ((int) $user->funcao !== $role) {
            $user->forceFill(['funcao' => $role])->save();
        }

        $request->session()->put('active_unit', ActiveUnitSessionData::fromUnit($unit));
        $request->session()->put('active_role', $role);

        return redirect()->route('dashboard')->with('success', 'Sessao atualizada com sucesso!');
    }

    private function allowedUnits($user)
    {
        if ($this->originalRole($user) === 7) {
            return Unidade::query()
                ->where('tb2_status', 1)
                ->orderBy('tb2_tipo')
                ->orderBy('tb2_nome')
                ->get(['tb2_id', 'tb2_nome', 'tb2_endereco', 'tb2_cnpj', 'tb2_tipo', 'matriz_id'])
                ->load('matriz:id,nome');
        }

        $matrixId = $this->resolveUserMatrixId($user);

        if ($matrixId <= 0) {
            return collect();
        }

        return Unidade::query()
            ->where('matriz_id', $matrixId)
            ->where('tb2_status', 1)
            ->orderBy('tb2_tipo')
            ->orderBy('tb2_nome')
            ->get(['tb2_id', 'tb2_nome', 'tb2_endereco', 'tb2_cnpj', 'tb2_tipo', 'matriz_id'])
            ->load('matriz:id,nome');
    }

    private function ensureCanSwitchUnit($user): void
    {
        $roleOriginal = $this->originalRole($user);

        if (! $user || ! array_key_exists($roleOriginal, self::ROLE_OPTIONS)) {
            abort(403);
        }
    }

    private function originalRole($user): int
    {
        return (int) ($user?->funcao_original ?? $user?->funcao ?? -1);
    }

    private function canUseRole(int $originalRole, int $role): bool
    {
        return array_key_exists($role, self::ROLE_OPTIONS) && $role <= $originalRole;
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

    private function groupUnitsByMatrix($units)
    {
        return $units
            ->groupBy(fn (array $unit) => $unit['matrixId'] > 0 ? 'matrix-' . $unit['matrixId'] : 'unit-' . $unit['id'])
            ->map(function ($group) {
                $first = $group->first();
                $matrixUnit = $group->first(fn (array $unit) => strcasecmp($unit['type'], 'matriz') === 0);
                $branches = $group
                    ->reject(fn (array $unit) => strcasecmp($unit['type'], 'matriz') === 0)
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

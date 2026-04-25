<?php

namespace App\Http\Controllers;

use App\Models\Unidade;
use App\Support\ActiveUnitSessionData;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitSwitchController extends Controller
{
    private const BOSS_SWITCHABLE_ROLES = [7, 0, 1, 2, 3, 4];

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
                'active' => (int) ($request->session()->get('active_unit.id')) === $unit->tb2_id,
            ])
            ->values();

        $matrixUnits = $units
            ->filter(fn (array $unit) => strcasecmp($unit['type'], 'matriz') === 0)
            ->values();

        $branchUnits = $units
            ->reject(fn (array $unit) => strcasecmp($unit['type'], 'matriz') === 0)
            ->values();

        $roles = collect(self::ROLE_OPTIONS)
            ->filter(function (string $label, int $value) use ($originalRole) {
                if ($originalRole === 7) {
                    return in_array($value, self::BOSS_SWITCHABLE_ROLES, true);
                }

                return $value >= $originalRole;
            })
            ->map(fn (string $label, int $value) => [
                'value' => $value,
                'label' => $label,
                'active' => $currentRole === $value,
            ])
            ->values();

        return Inertia::render('Reports/SwitchUnit', [
            'units' => $units,
            'matrixUnits' => $matrixUnits,
            'branchUnits' => $branchUnits,
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

        $request->session()->put('active_unit', ActiveUnitSessionData::fromUnit($unit));
        $request->session()->put('active_role', $role);

        return redirect()->route('dashboard')->with('success', 'Sessao atualizada com sucesso!');
    }

    private function allowedUnits($user)
    {
        return ManagementScope::managedUnits(
            $user,
            ['tb2_id', 'tb2_nome', 'tb2_endereco', 'tb2_cnpj', 'tb2_tipo', 'matriz_id']
        )->load('matriz:id,nome');
    }

    private function ensureCanSwitchUnit($user): void
    {
        $roleOriginal = $this->originalRole($user);

        if (! $user || ! in_array($roleOriginal, [7, 0, 1, 2, 3], true)) {
            abort(403);
        }
    }

    private function originalRole($user): int
    {
        return (int) ($user?->funcao_original ?? $user?->funcao ?? -1);
    }

    private function canUseRole(int $originalRole, int $role): bool
    {
        if ($originalRole === 7) {
            return in_array($role, self::BOSS_SWITCHABLE_ROLES, true);
        }

        return $role >= $originalRole;
    }
}

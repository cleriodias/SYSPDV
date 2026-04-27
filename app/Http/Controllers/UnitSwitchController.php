<?php

namespace App\Http\Controllers;

use App\Support\ActiveUnitSessionData;
use App\Support\ProfileSwitchData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitSwitchController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureCanSwitchUnit($user);

        return Inertia::render('Reports/SwitchUnit', ProfileSwitchData::forRequest($request));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanSwitchUnit($user);
        $originalRole = ProfileSwitchData::originalRole($user);

        $validated = $request->validate([
            'unit_id' => ['required', 'integer'],
            'role' => ['required', 'integer', 'between:0,7'],
        ]);

        $units = ProfileSwitchData::allowedUnits($user);
        $unit = $units->firstWhere('tb2_id', (int) $validated['unit_id']);
        $role = (int) $validated['role'];

        if (
            ! $unit
            || ProfileSwitchData::roleLabel($role) === '---'
            || ! ProfileSwitchData::canUseRole($originalRole, $role)
            || ! ProfileSwitchData::canSelectUnit($unit)
            || ! ProfileSwitchData::isValidSelection($user, $unit, $role)
        ) {
            abort(403);
        }

        $request->session()->put('active_unit', ActiveUnitSessionData::fromUnit($unit));
        $request->session()->put('active_role', $role);

        return redirect()->route('dashboard')->with('success', 'Sessao atualizada com sucesso!');
    }

    private function ensureCanSwitchUnit($user): void
    {
        if (! ProfileSwitchData::canAccess($user)) {
            abort(403);
        }
    }
}

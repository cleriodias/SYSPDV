<?php

namespace App\Http\Middleware;

use App\Models\Unidade;
use App\Support\ManagementScope;
use Closure;
use Illuminate\Http\Request;

class ApplyActiveRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $originalRole = ManagementScope::originalRole($user);
            $activeRole = (int) $request->session()->get('active_role', $originalRole);

            $maximumRole = $originalRole === 7 ? 7 : 6;
            $minimumRole = $originalRole === 7 ? 0 : $originalRole;

            if ($activeRole < $minimumRole || $activeRole > $maximumRole) {
                $activeRole = $originalRole;
            }

            $activeUnitId = (int) $request->session()->get('active_unit.id', 0);
            $activeUnit = $activeUnitId > 0
                ? Unidade::query()->select('tb2_id', 'tb2_nome')->find($activeUnitId)
                : null;
            $isBossUnit = ManagementScope::isBossUnit($activeUnit);

            if ($isBossUnit && ManagementScope::isBossAccount($user) && $activeRole !== 7) {
                $activeRole = 7;
            } elseif (($isBossUnit && ! ManagementScope::isBossAccount($user)) || (! $isBossUnit && $activeRole === 7)) {
                $activeRole = $originalRole === 7 ? 0 : $originalRole;
            }

            $request->session()->put('active_role', $activeRole);
            $user->setAttribute('funcao', $activeRole);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Unidade;
use App\Support\ManagementScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUnitLoginAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ManagementScope::originalRole($user) === 7) {
            return $next($request);
        }

        $activeUnit = $request->session()->get('active_unit');
        $unitId = is_array($activeUnit)
            ? (int) ($activeUnit['id'] ?? $activeUnit['tb2_id'] ?? 0)
            : (is_object($activeUnit) ? (int) ($activeUnit->id ?? $activeUnit->tb2_id ?? 0) : 0);

        if ($unitId <= 0) {
            return $next($request);
        }

        $canAccess = Unidade::query()
            ->where('tb2_id', $unitId)
            ->where('tb2_status', 1)
            ->where('login_liberado', 1)
            ->exists();

        if ($canAccess) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'O acesso desta unidade foi bloqueado pelo controle financeiro.');
    }
}

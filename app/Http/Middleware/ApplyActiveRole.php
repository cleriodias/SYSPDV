<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApplyActiveRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $originalRole = (int) ($user->funcao_original ?? $user->funcao ?? 0);
            $activeRole = (int) $request->session()->get('active_role', $originalRole);

            $maximumRole = $originalRole === 7 ? 7 : 6;
            $minimumRole = $originalRole === 7 ? 0 : $originalRole;

            if ($activeRole < $minimumRole || $activeRole > $maximumRole) {
                $activeRole = $originalRole;
            }

            $request->session()->put('active_role', $activeRole);
            $user->setAttribute('funcao', $activeRole);
        }

        return $next($request);
    }
}

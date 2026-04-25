<?php

namespace App\Http\Middleware;

use App\Models\Unidade;
use App\Support\ActiveUnitSessionData;
use Closure;
use Illuminate\Http\Request;

class EnsureActiveUnit
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $request->session()->has('active_unit')) {
            $unitId = (int) ($user->tb2_id ?? 0);

            if ($unitId > 0) {
                $unit = Unidade::active()
                    ->select('tb2_id', 'tb2_nome', 'tb2_endereco', 'tb2_cnpj', 'tb2_tipo', 'matriz_id')
                    ->with('matriz:id,nome')
                    ->find($unitId);

                if ($unit) {
                    $request->session()->put('active_unit', ActiveUnitSessionData::fromUnit($unit));
                }
            }
        }

        return $next($request);
    }
}

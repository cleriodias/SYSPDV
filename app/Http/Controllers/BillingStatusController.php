<?php

namespace App\Http\Controllers;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingStatusController extends Controller
{
    public function toggleMatrixPayment(Request $request, Matriz $matriz): RedirectResponse
    {
        abort_unless(ManagementScope::isBoss($request->user()), 403);

        $matriz->forceFill([
            'pagamento_ativo' => ! (bool) $matriz->pagamento_ativo,
        ])->save();

        return back()->with('success', 'Status de pagamento da matriz atualizado.');
    }

    public function toggleUnitPayment(Request $request, Unidade $unit): RedirectResponse
    {
        abort_unless(ManagementScope::isBoss($request->user()), 403);

        $unit->forceFill([
            'pagamento_ativo' => ! (bool) $unit->pagamento_ativo,
        ])->save();

        return back()->with('success', 'Status de pagamento da unidade atualizado.');
    }

    public function toggleUnitLogin(Request $request, Unidade $unit): RedirectResponse
    {
        abort_unless(ManagementScope::isBoss($request->user()), 403);

        $unit->forceFill([
            'login_liberado' => ! (bool) $unit->login_liberado,
        ])->save();

        return back()->with('success', 'Permissao de login da unidade atualizada.');
    }
}

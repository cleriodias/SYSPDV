<?php

namespace App\Http\Controllers;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingStatusController extends Controller
{
    public function toggleMatrixStatus(Request $request, Matriz $matriz): RedirectResponse
    {
        abort_unless(ManagementScope::isBoss($request->user()), 403);

        $nextStatus = (int) $matriz->status === 1 ? 0 : 1;

        $matriz->forceFill([
            'status' => $nextStatus,
        ])->save();

        $matriz->units()
            ->where('tb2_tipo', 'matriz')
            ->update([
                'tb2_status' => $nextStatus,
            ]);

        return back()->with(
            'success',
            $nextStatus === 1
                ? 'Status da matriz atualizado para ativa.'
                : 'Status da matriz atualizado para inativa.'
        );
    }

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

    public function toggleUnitStatus(Request $request, Unidade $unit): RedirectResponse
    {
        abort_unless(ManagementScope::isBoss($request->user()), 403);

        $unit->forceFill([
            'tb2_status' => (int) $unit->tb2_status === 1 ? 0 : 1,
        ])->save();

        return back()->with(
            'success',
            (int) $unit->tb2_status === 1
                ? 'Status da unidade atualizado para ativa.'
                : 'Status da unidade atualizado para inativa.'
        );
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

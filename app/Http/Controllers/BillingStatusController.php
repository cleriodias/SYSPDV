<?php

namespace App\Http\Controllers;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Support\BillingPlanSettings;
use App\Support\ManagementScope;
use App\Support\RecurringBillingService;
use Carbon\Carbon;
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

        $planSettings = BillingPlanSettings::current();
        $markedAsPaid = app(RecurringBillingService::class)->toggleMatrixPayment(
            $matriz,
            Carbon::today(),
            (float) ($planSettings['matrix_monthly_price'] ?? 250)
        );

        return back()->with(
            'success',
            $markedAsPaid
                ? 'Cobrancas mensais da matriz marcadas como pagas.'
                : 'Cobranca mensal atual da matriz reaberta.'
        );
    }

    public function toggleUnitPayment(Request $request, Unidade $unit): RedirectResponse
    {
        abort_unless(ManagementScope::isBoss($request->user()), 403);

        $planSettings = BillingPlanSettings::current();
        $markedAsPaid = app(RecurringBillingService::class)->toggleUnitPayment(
            $unit,
            Carbon::today(),
            (float) ($planSettings['branch_monthly_price'] ?? 180)
        );

        return back()->with(
            'success',
            $markedAsPaid
                ? 'Cobrancas mensais da unidade marcadas como pagas.'
                : 'Cobranca mensal atual da unidade reaberta.'
        );
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

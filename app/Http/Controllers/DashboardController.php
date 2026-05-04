<?php

namespace App\Http\Controllers;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Support\BillingPlanSettings;
use App\Support\ManagementScope;
use App\Support\ProfileSwitchData;
use App\Support\RecurringBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (ManagementScope::isBoss($user)) {
            $today = Carbon::today();
            $planSettings = BillingPlanSettings::current();
            $showInactive = $request->boolean('show_inactive');
            $matrizes = Matriz::query()
                ->with(['units' => fn ($query) => $query->orderBy('tb2_nome')])
                ->orderBy('nome')
                ->get()
                ->values();

            app(RecurringBillingService::class)->syncForDashboard($matrizes, $today, $planSettings);
            $billingSummaries = app(RecurringBillingService::class)->buildDashboardSummaries($matrizes, $today);

            $matrizes = $matrizes
                ->filter(fn (Matriz $matriz) => $showInactive || (int) ($matriz->status ?? 0) === 1)
                ->map(function (Matriz $matriz) use ($planSettings, $showInactive, $billingSummaries) {
                    $matrixFee = (float) ($matriz->plano_mensal_valor ?? $planSettings['matrix_monthly_price']);
                    $units = $matriz->units ?? collect();
                    $matrixUnit = $units->first(
                        fn (Unidade $unit) => (string) ($unit->tb2_tipo ?? 'filial') === 'matriz'
                    );

                    $branchUnits = $units->filter(
                        fn (Unidade $unit) => (string) ($unit->tb2_tipo ?? 'filial') === 'filial'
                    );

                    $branches = $branchUnits
                        ->filter(fn (Unidade $unit) => $showInactive || (int) ($unit->tb2_status ?? 0) === 1)
                        ->map(function (Unidade $unit) use ($planSettings, $billingSummaries) {
                        $billing = $billingSummaries['filial'][(int) $unit->tb2_id] ?? [];

                        return [
                            'id' => (int) $unit->tb2_id,
                            'name' => (string) $unit->tb2_nome,
                            'status' => (int) ($unit->tb2_status ?? 0),
                            'payment_status' => (bool) ($unit->pagamento_ativo ?? true),
                            'payment_status_key' => (string) ($billing['status_key'] ?? 'pendente'),
                            'payment_status_label' => (string) ($billing['status_label'] ?? 'Pendente'),
                            'payment_pending_count' => (int) ($billing['pending_count'] ?? 0),
                            'payment_pending_amount' => round((float) ($billing['pending_amount'] ?? 0), 2),
                            'payment_overdue_count' => (int) ($billing['overdue_count'] ?? 0),
                            'payment_overdue_amount' => round((float) ($billing['overdue_amount'] ?? 0), 2),
                            'payment_due_at' => $billing['current_charge_due_at'] ?? null,
                            'payment_paid_at' => $billing['current_charge_paid_at'] ?? null,
                            'payment_amount' => round((float) ($billing['current_charge_amount'] ?? $unit->plano_mensal_valor ?? $planSettings['branch_monthly_price']), 2),
                            'login_enabled' => (bool) ($unit->login_liberado ?? true),
                            'monthly_value' => (float) ($unit->plano_mensal_valor ?? $planSettings['branch_monthly_price']),
                            'contracted_at' => optional($unit->plano_contratado_em ?? $unit->created_at)?->format('d/m/y'),
                        ];
                    })->values();

                    $branchMonthlyTotal = round((float) $branches->sum('monthly_value'), 2);
                    $totalMonthly = round($matrixFee + $branchMonthlyTotal, 2);
                    $matrixBilling = $billingSummaries['matriz'][(int) $matriz->id] ?? [];

                    return [
                        'id' => (int) $matriz->id,
                        'name' => (string) $matriz->nome,
                        'cnpj' => $matriz->cnpj,
                        'status' => (int) ($matriz->status ?? 0),
                        'payment_status' => (bool) ($matriz->pagamento_ativo ?? true),
                        'payment_status_key' => (string) ($matrixBilling['status_key'] ?? 'pendente'),
                        'payment_status_label' => (string) ($matrixBilling['status_label'] ?? 'Pendente'),
                        'payment_pending_count' => (int) ($matrixBilling['pending_count'] ?? 0),
                        'payment_pending_amount' => round((float) ($matrixBilling['pending_amount'] ?? 0), 2),
                        'payment_overdue_count' => (int) ($matrixBilling['overdue_count'] ?? 0),
                        'payment_overdue_amount' => round((float) ($matrixBilling['overdue_amount'] ?? 0), 2),
                        'payment_due_at' => $matrixBilling['current_charge_due_at'] ?? null,
                        'payment_paid_at' => $matrixBilling['current_charge_paid_at'] ?? null,
                        'payment_amount' => round((float) ($matrixBilling['current_charge_amount'] ?? $matrixFee), 2),
                        'matrix_unit_id' => $matrixUnit ? (int) $matrixUnit->tb2_id : null,
                        'matrix_unit_name' => trim((string) ($matrixUnit?->tb2_nome ?? '')) !== ''
                            ? trim((string) $matrixUnit->tb2_nome)
                            : ((string) $matriz->nome !== '' ? (string) $matriz->nome : 'Matriz'),
                        'matrix_login_enabled' => (bool) ($matrixUnit?->login_liberado ?? true),
                        'matrix_unit_status' => (int) ($matrixUnit?->tb2_status ?? 0),
                        'matrix_monthly_value' => $matrixFee,
                        'matrix_contracted_at' => optional($matriz->plano_contratado_em ?? $matriz->created_at)?->format('d/m/y'),
                        'branches_count' => $branches->count(),
                        'branch_monthly_total' => $branchMonthlyTotal,
                        'total_monthly_value' => $totalMonthly,
                        'branches' => $branches->all(),
                    ];
                })
                ->values();

            return Inertia::render('Boss/Dashboard', [
                'planSettings' => $planSettings,
                'filters' => [
                    'show_inactive' => $showInactive,
                ],
                'summary' => [
                    'matrices_count' => $matrizes->count(),
                    'branches_count' => $matrizes->sum('branches_count'),
                    'matrix_monthly_total' => round((float) $matrizes->sum('matrix_monthly_value'), 2),
                    'branch_monthly_total' => round((float) $matrizes->sum('branch_monthly_total'), 2),
                    'grand_monthly_total' => round((float) $matrizes->sum('total_monthly_value'), 2),
                    'pending_billing_count' => (int) $matrizes->sum('payment_pending_count')
                        + (int) $matrizes->sum(fn (array $matriz) => collect($matriz['branches'] ?? [])->sum('payment_pending_count')),
                    'paid_billing_count' => (int) $matrizes->sum(fn (array $matriz) => ($matriz['payment_status_key'] ?? null) === 'pago' ? 1 : 0)
                        + (int) $matrizes->sum(fn (array $matriz) => collect($matriz['branches'] ?? [])->filter(
                            fn (array $branch) => ($branch['payment_status_key'] ?? null) === 'pago'
                        )->count()),
                    'paid_billing_amount' => round(
                        (float) $matrizes->sum(fn (array $matriz) => ($matriz['payment_status_key'] ?? null) === 'pago'
                            ? (float) ($matriz['payment_amount'] ?? 0)
                            : 0)
                        + (float) $matrizes->sum(fn (array $matriz) => collect($matriz['branches'] ?? [])->sum(
                            fn (array $branch) => ($branch['payment_status_key'] ?? null) === 'pago'
                                ? (float) ($branch['payment_amount'] ?? 0)
                                : 0
                        )),
                        2
                    ),
                    'overdue_billing_count' => (int) $matrizes->sum('payment_overdue_count')
                        + (int) $matrizes->sum(fn (array $matriz) => collect($matriz['branches'] ?? [])->sum('payment_overdue_count')),
                    'overdue_billing_amount' => round(
                        (float) $matrizes->sum('payment_overdue_amount')
                        + (float) $matrizes->sum(fn (array $matriz) => collect($matriz['branches'] ?? [])->sum('payment_overdue_amount')),
                        2
                    ),
                ],
                'matrizes' => $matrizes->all(),
            ]);
        }

        $profileSwitch = null;

        if ((int) ($user?->funcao ?? -1) === 0 && ProfileSwitchData::canAccess($user)) {
            $profileSwitch = ProfileSwitchData::forRequest($request);
        }

        return Inertia::render('Dashboard', [
            'profileSwitch' => $profileSwitch,
        ]);
    }
}

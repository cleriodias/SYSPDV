<?php

namespace App\Http\Controllers;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Support\BillingPlanSettings;
use App\Support\ManagementScope;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();

        if (ManagementScope::isBoss($user)) {
            $planSettings = BillingPlanSettings::current();
            $matrizes = Matriz::query()
                ->with(['units' => fn ($query) => $query->orderBy('tb2_nome')])
                ->orderBy('nome')
                ->get()
                ->map(function (Matriz $matriz) use ($planSettings) {
                    $matrixFee = (float) ($matriz->plano_mensal_valor ?? $planSettings['matrix_monthly_price']);
                    $units = $matriz->units ?? collect();
                    $matrixUnit = $units->first(
                        fn (Unidade $unit) => (string) ($unit->tb2_tipo ?? 'filial') === 'matriz'
                    );

                    $branchUnits = $units->filter(
                        fn (Unidade $unit) => (string) ($unit->tb2_tipo ?? 'filial') === 'filial'
                    );

                    $branches = $branchUnits->map(function (Unidade $unit) use ($planSettings) {
                        return [
                            'id' => (int) $unit->tb2_id,
                            'name' => (string) $unit->tb2_nome,
                            'status' => (int) ($unit->tb2_status ?? 0),
                            'payment_status' => (bool) ($unit->pagamento_ativo ?? true),
                            'login_enabled' => (bool) ($unit->login_liberado ?? true),
                            'monthly_value' => (float) ($unit->plano_mensal_valor ?? $planSettings['branch_monthly_price']),
                            'contracted_at' => optional($unit->plano_contratado_em ?? $unit->created_at)?->format('d/m/Y H:i'),
                        ];
                    })->values();

                    $branchMonthlyTotal = round((float) $branches->sum('monthly_value'), 2);
                    $totalMonthly = round($matrixFee + $branchMonthlyTotal, 2);

                    return [
                        'id' => (int) $matriz->id,
                        'name' => (string) $matriz->nome,
                        'cnpj' => $matriz->cnpj,
                        'status' => (int) ($matriz->status ?? 0),
                        'payment_status' => (bool) ($matriz->pagamento_ativo ?? true),
                        'matrix_unit_id' => $matrixUnit ? (int) $matrixUnit->tb2_id : null,
                        'matrix_login_enabled' => (bool) ($matrixUnit?->login_liberado ?? true),
                        'matrix_unit_status' => (int) ($matrixUnit?->tb2_status ?? 0),
                        'matrix_monthly_value' => $matrixFee,
                        'matrix_contracted_at' => optional($matriz->plano_contratado_em ?? $matriz->created_at)?->format('d/m/Y H:i'),
                        'branches_count' => $branches->count(),
                        'branch_monthly_total' => $branchMonthlyTotal,
                        'total_monthly_value' => $totalMonthly,
                        'branches' => $branches->all(),
                    ];
                })
                ->values();

            return Inertia::render('Boss/Dashboard', [
                'planSettings' => $planSettings,
                'summary' => [
                    'matrices_count' => $matrizes->count(),
                    'branches_count' => $matrizes->sum('branches_count'),
                    'matrix_monthly_total' => round((float) $matrizes->sum('matrix_monthly_value'), 2),
                    'branch_monthly_total' => round((float) $matrizes->sum('branch_monthly_total'), 2),
                    'grand_monthly_total' => round((float) $matrizes->sum('total_monthly_value'), 2),
                ],
                'matrizes' => $matrizes->all(),
            ]);
        }

        return Inertia::render('Dashboard');
    }
}

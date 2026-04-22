<?php

namespace App\Http\Controllers;

use App\Support\BillingPlanSettings;
use App\Support\ManagementScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingPlanController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        if (! ManagementScope::isBoss($request->user())) {
            abort(403, 'Acesso negado.');
        }

        $data = $request->validate([
            'matrix_monthly_price' => ['required', 'numeric', 'min:0'],
            'branch_monthly_price' => ['required', 'numeric', 'min:0'],
            'hosting_monthly_price' => ['required', 'numeric', 'min:0'],
            'purchase_matrix_price' => ['required', 'numeric', 'min:0'],
            'purchase_branch_price' => ['required', 'numeric', 'min:0'],
            'purchase_installments' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $settings = BillingPlanSettings::model();
        $settings->fill($data);
        $settings->save();

        return redirect()->route('dashboard')->with(
            'success',
            'Planos atualizados com sucesso. Os novos valores serao aplicados apenas em futuras contratacoes.'
        );
    }
}

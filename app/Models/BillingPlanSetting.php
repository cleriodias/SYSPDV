<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingPlanSetting extends Model
{
    use HasFactory;

    protected $table = 'billing_plan_settings';

    protected $fillable = [
        'matrix_monthly_price',
        'branch_monthly_price',
        'hosting_monthly_price',
        'purchase_matrix_price',
        'purchase_branch_price',
        'purchase_installments',
    ];

    protected $casts = [
        'matrix_monthly_price' => 'float',
        'branch_monthly_price' => 'float',
        'hosting_monthly_price' => 'float',
        'purchase_matrix_price' => 'float',
        'purchase_branch_price' => 'float',
        'purchase_installments' => 'integer',
    ];
}

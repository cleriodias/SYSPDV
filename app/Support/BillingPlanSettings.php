<?php

namespace App\Support;

use App\Models\BillingPlanSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BillingPlanSettings
{
    public const DEFAULTS = [
        'matrix_monthly_price' => 250.00,
        'branch_monthly_price' => 180.00,
        'hosting_monthly_price' => 70.00,
        'purchase_matrix_price' => 10000.00,
        'purchase_branch_price' => 5000.00,
        'purchase_installments' => 15,
    ];

    public static function current(): array
    {
        try {
            if (! Schema::hasTable('billing_plan_settings')) {
                return self::DEFAULTS;
            }

            $record = BillingPlanSetting::query()->first();

            if (! $record) {
                return self::DEFAULTS;
            }

            return [
                'matrix_monthly_price' => (float) ($record->matrix_monthly_price ?? self::DEFAULTS['matrix_monthly_price']),
                'branch_monthly_price' => (float) ($record->branch_monthly_price ?? self::DEFAULTS['branch_monthly_price']),
                'hosting_monthly_price' => (float) ($record->hosting_monthly_price ?? self::DEFAULTS['hosting_monthly_price']),
                'purchase_matrix_price' => (float) ($record->purchase_matrix_price ?? self::DEFAULTS['purchase_matrix_price']),
                'purchase_branch_price' => (float) ($record->purchase_branch_price ?? self::DEFAULTS['purchase_branch_price']),
                'purchase_installments' => (int) ($record->purchase_installments ?? self::DEFAULTS['purchase_installments']),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return self::DEFAULTS;
        }
    }

    public static function model(): BillingPlanSetting
    {
        return BillingPlanSetting::query()->firstOrCreate([], self::DEFAULTS);
    }
}

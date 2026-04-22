<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plan_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('matrix_monthly_price', 10, 2)->default(250);
            $table->decimal('branch_monthly_price', 10, 2)->default(180);
            $table->decimal('hosting_monthly_price', 10, 2)->default(70);
            $table->decimal('purchase_matrix_price', 10, 2)->default(10000);
            $table->decimal('purchase_branch_price', 10, 2)->default(5000);
            $table->unsignedInteger('purchase_installments')->default(15);
            $table->timestamps();
        });

        DB::table('billing_plan_settings')->insert([
            'matrix_monthly_price' => 250,
            'branch_monthly_price' => 180,
            'hosting_monthly_price' => 70,
            'purchase_matrix_price' => 10000,
            'purchase_branch_price' => 5000,
            'purchase_installments' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('matrizes', function (Blueprint $table) {
            $table->decimal('plano_mensal_valor', 10, 2)->default(250)->after('status');
            $table->timestamp('plano_contratado_em')->nullable()->after('plano_mensal_valor');
        });

        Schema::table('tb2_unidades', function (Blueprint $table) {
            $table->decimal('plano_mensal_valor', 10, 2)->nullable()->after('tb2_status');
            $table->timestamp('plano_contratado_em')->nullable()->after('plano_mensal_valor');
        });

        DB::table('matrizes')
            ->whereNull('plano_contratado_em')
            ->update([
                'plano_mensal_valor' => 250,
                'plano_contratado_em' => now(),
            ]);

        DB::table('tb2_unidades')
            ->where(function ($query) {
                $query->whereNull('plano_mensal_valor')
                    ->orWhereNull('plano_contratado_em');
            })
            ->update([
                'plano_mensal_valor' => DB::raw("CASE WHEN COALESCE(tb2_tipo, 'filial') = 'matriz' THEN 250 ELSE 180 END"),
                'plano_contratado_em' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('tb2_unidades', function (Blueprint $table) {
            $table->dropColumn(['plano_mensal_valor', 'plano_contratado_em']);
        });

        Schema::table('matrizes', function (Blueprint $table) {
            $table->dropColumn(['plano_mensal_valor', 'plano_contratado_em']);
        });

        Schema::dropIfExists('billing_plan_settings');
    }
};

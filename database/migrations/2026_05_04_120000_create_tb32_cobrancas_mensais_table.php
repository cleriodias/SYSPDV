<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb32_cobrancas_mensais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matriz_id')->constrained('matrizes')->cascadeOnDelete();
            $table->unsignedBigInteger('tb2_id')->nullable();
            $table->string('referencia_tipo', 20);
            $table->unsignedBigInteger('referencia_id');
            $table->date('competencia');
            $table->date('data_vencimento');
            $table->decimal('valor_cobrado', 10, 2);
            $table->string('status_pagamento', 20)->default('pendente');
            $table->timestamp('pago_em')->nullable();
            $table->timestamps();

            $table->foreign('tb2_id')->references('tb2_id')->on('tb2_unidades')->nullOnDelete();
            $table->unique(['referencia_tipo', 'referencia_id', 'competencia'], 'tb32_cobrancas_ref_comp_unique');
            $table->index(['matriz_id', 'competencia'], 'tb32_cobrancas_matriz_comp_index');
            $table->index(['tb2_id', 'competencia'], 'tb32_cobrancas_unidade_comp_index');
            $table->index(['status_pagamento', 'data_vencimento'], 'tb32_cobrancas_status_venc_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb32_cobrancas_mensais');
    }
};

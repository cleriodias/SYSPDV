<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb29_nfe_lancamentos', function (Blueprint $table) {
            $table->bigIncrements('tb29_id');
            $table->string('tb29_numero', 30)->nullable()->unique();
            $table->unsignedBigInteger('tb2_id');
            $table->unsignedBigInteger('matriz_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('tb29_status', 30)->default('rascunho');
            $table->string('tb29_tipo_operacao', 30);
            $table->string('tb29_finalidade', 30)->nullable();
            $table->date('tb29_data_lancamento');
            $table->date('tb29_data_emissao')->nullable();
            $table->date('tb29_data_competencia')->nullable();
            $table->json('tb29_destinatario')->nullable();
            $table->json('tb29_comercial')->nullable();
            $table->json('tb29_itens')->nullable();
            $table->json('tb29_pagamento')->nullable();
            $table->json('tb29_totais')->nullable();
            $table->json('tb29_pendencias')->nullable();
            $table->longText('tb29_observacoes')->nullable();
            $table->json('tb29_historico')->nullable();
            $table->timestamps();

            $table
                ->foreign('tb2_id')
                ->references('tb2_id')
                ->on('tb2_unidades')
                ->cascadeOnDelete();

            $table
                ->foreign('matriz_id')
                ->references('id')
                ->on('matrizes')
                ->cascadeOnDelete();

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['tb2_id', 'tb29_status']);
            $table->index(['matriz_id', 'tb29_status']);
            $table->index(['tb2_id', 'tb29_data_lancamento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb29_nfe_lancamentos');
    }
};

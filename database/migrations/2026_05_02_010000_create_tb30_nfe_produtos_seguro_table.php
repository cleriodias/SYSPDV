<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb30_nfe_produtos_seguro', function (Blueprint $table) {
            $table->bigIncrements('tb30_id');
            $table->unsignedBigInteger('matriz_id');
            $table->unsignedBigInteger('tb2_id')->nullable();
            $table->string('tb30_codigo', 30);
            $table->string('tb30_nome', 255);
            $table->string('tb30_seguradora', 160);
            $table->string('tb30_ramo', 120);
            $table->string('tb30_modalidade', 120)->nullable();
            $table->string('tb30_tipo_contratacao', 80);
            $table->string('tb30_periodicidade', 40);
            $table->string('tb30_cfop', 4);
            $table->string('tb30_ncm', 8)->nullable();
            $table->string('tb30_unidade_padrao', 10)->default('UN');
            $table->decimal('tb30_premio_base', 12, 2)->default(0);
            $table->decimal('tb30_comissao_percentual', 8, 2)->default(0);
            $table->text('tb30_regras')->nullable();
            $table->unsignedTinyInteger('tb30_status')->default(1);
            $table->timestamps();

            $table
                ->foreign('matriz_id')
                ->references('id')
                ->on('matrizes')
                ->cascadeOnDelete();

            $table
                ->foreign('tb2_id')
                ->references('tb2_id')
                ->on('tb2_unidades')
                ->nullOnDelete();

            $table->unique(['matriz_id', 'tb30_codigo']);
            $table->index(['matriz_id', 'tb30_status']);
            $table->index(['tb2_id', 'tb30_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb30_nfe_produtos_seguro');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb31_nfe_seguradoras', function (Blueprint $table) {
            $table->bigIncrements('tb31_id');
            $table->foreignId('matriz_id')->constrained('matrizes')->cascadeOnDelete();
            $table->string('tb31_nome_fantasia', 160);
            $table->string('tb31_razao_social', 160)->nullable();
            $table->string('tb31_cnpj', 20)->nullable();
            $table->string('tb31_codigo_susep', 60)->nullable();
            $table->unsignedTinyInteger('tb31_status')->default(1);
            $table->boolean('tb31_usa_integracao')->default(false);
            $table->string('tb31_codigo_externo_integracao', 100)->nullable();
            $table->text('tb31_observacoes_operacionais')->nullable();
            $table->timestamps();

            $table->unique(['matriz_id', 'tb31_nome_fantasia']);
            $table->index(['matriz_id', 'tb31_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb31_nfe_seguradoras');
    }
};

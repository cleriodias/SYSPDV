<?php

use App\Models\Aplicacao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matrizes', function (Blueprint $table) {
            $table->unsignedBigInteger('tb28_id')
                ->default(Aplicacao::PADARIA_NFE)
                ->after('cnpj');

            $table->foreign('tb28_id')
                ->references('tb28_id')
                ->on('tb28_aplicacoes');
        });

        DB::table('matrizes')->update([
            'tb28_id' => Aplicacao::PADARIA_NFE,
        ]);
    }

    public function down(): void
    {
        Schema::table('matrizes', function (Blueprint $table) {
            $table->dropForeign(['tb28_id']);
            $table->dropColumn('tb28_id');
        });
    }
};

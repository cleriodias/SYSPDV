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
        Schema::table('tb28_aplicacoes', function (Blueprint $table) {
            $table->string('tb28_rota_inicial', 150)
                ->default('dashboard')
                ->after('tb28_slug');
        });

        DB::table('tb28_aplicacoes')
            ->where('tb28_id', Aplicacao::PADARIA_NFE)
            ->update(['tb28_rota_inicial' => Aplicacao::defaultInitialRoute(Aplicacao::PADARIA_NFE)]);

        DB::table('tb28_aplicacoes')
            ->where('tb28_id', Aplicacao::PADARIA)
            ->update(['tb28_rota_inicial' => Aplicacao::defaultInitialRoute(Aplicacao::PADARIA)]);

        DB::table('tb28_aplicacoes')
            ->where('tb28_id', Aplicacao::NFE)
            ->update(['tb28_rota_inicial' => Aplicacao::defaultInitialRoute(Aplicacao::NFE)]);
    }

    public function down(): void
    {
        Schema::table('tb28_aplicacoes', function (Blueprint $table) {
            $table->dropColumn('tb28_rota_inicial');
        });
    }
};

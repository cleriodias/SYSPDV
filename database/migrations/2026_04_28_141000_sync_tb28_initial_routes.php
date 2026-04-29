<?php

use App\Models\Aplicacao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
        DB::table('tb28_aplicacoes')
            ->where('tb28_id', Aplicacao::PADARIA_NFE)
            ->update(['tb28_rota_inicial' => 'dashboard']);

        DB::table('tb28_aplicacoes')
            ->where('tb28_id', Aplicacao::PADARIA)
            ->update(['tb28_rota_inicial' => 'dashboard']);

        DB::table('tb28_aplicacoes')
            ->where('tb28_id', Aplicacao::NFE)
            ->update(['tb28_rota_inicial' => 'settings.fiscal?unit_id={unit_id}']);
    }
};

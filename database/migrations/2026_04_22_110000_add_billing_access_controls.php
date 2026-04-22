<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matrizes', function (Blueprint $table) {
            if (! Schema::hasColumn('matrizes', 'pagamento_ativo')) {
                $table->boolean('pagamento_ativo')->default(true)->after('plano_contratado_em');
            }
        });

        Schema::table('tb2_unidades', function (Blueprint $table) {
            if (! Schema::hasColumn('tb2_unidades', 'pagamento_ativo')) {
                $table->boolean('pagamento_ativo')->default(true)->after('plano_contratado_em');
            }

            if (! Schema::hasColumn('tb2_unidades', 'login_liberado')) {
                $table->boolean('login_liberado')->default(true)->after('pagamento_ativo');
            }
        });

        DB::table('matrizes')
            ->whereNull('pagamento_ativo')
            ->update(['pagamento_ativo' => 1]);

        DB::table('tb2_unidades')
            ->whereNull('pagamento_ativo')
            ->update(['pagamento_ativo' => 1]);

        DB::table('tb2_unidades')
            ->whereNull('login_liberado')
            ->update(['login_liberado' => 1]);
    }

    public function down(): void
    {
        Schema::table('tb2_unidades', function (Blueprint $table) {
            if (Schema::hasColumn('tb2_unidades', 'login_liberado')) {
                $table->dropColumn('login_liberado');
            }

            if (Schema::hasColumn('tb2_unidades', 'pagamento_ativo')) {
                $table->dropColumn('pagamento_ativo');
            }
        });

        Schema::table('matrizes', function (Blueprint $table) {
            if (Schema::hasColumn('matrizes', 'pagamento_ativo')) {
                $table->dropColumn('pagamento_ativo');
            }
        });
    }
};

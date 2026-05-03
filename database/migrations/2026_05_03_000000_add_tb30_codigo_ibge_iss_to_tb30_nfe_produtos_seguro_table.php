<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb30_nfe_produtos_seguro', function (Blueprint $table) {
            $table->string('tb30_codigo_ibge_iss', 7)
                ->nullable()
                ->after('tb30_uf_iss');
        });
    }

    public function down(): void
    {
        Schema::table('tb30_nfe_produtos_seguro', function (Blueprint $table) {
            $table->dropColumn('tb30_codigo_ibge_iss');
        });
    }
};

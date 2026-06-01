<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb1_produto', function (Blueprint $table) {
            $table->index(
                ['matriz_id', 'tb1_status', 'tb1_nome', 'produto_id'],
                'tb1_prod_matriz_status_nome_produto_idx'
            );
            $table->index(
                ['matriz_id', 'tb1_tipo', 'tb1_status', 'tb1_nome'],
                'tb1_prod_matriz_tipo_status_nome_idx'
            );
            $table->index(
                ['matriz_id', 'tb1_favorito', 'tb1_status', 'tb1_nome'],
                'tb1_prod_matriz_fav_status_nome_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tb1_produto', function (Blueprint $table) {
            $table->dropIndex('tb1_prod_matriz_status_nome_produto_idx');
            $table->dropIndex('tb1_prod_matriz_tipo_status_nome_idx');
            $table->dropIndex('tb1_prod_matriz_fav_status_nome_idx');
        });
    }
};

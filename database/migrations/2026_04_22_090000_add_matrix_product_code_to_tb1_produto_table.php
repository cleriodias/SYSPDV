<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb1_produto', function (Blueprint $table) {
            $table->foreignId('matriz_id')
                ->nullable()
                ->after('tb1_id')
                ->constrained('matrizes')
                ->cascadeOnDelete();
            $table->unsignedInteger('produto_id')
                ->nullable()
                ->after('matriz_id');
        });

        $defaultMatrixId = (int) DB::table('matrizes')->orderBy('id')->value('id');

        if ($defaultMatrixId > 0) {
            DB::table('tb1_produto')
                ->whereNull('matriz_id')
                ->update(['matriz_id' => $defaultMatrixId]);
        }

        DB::table('tb1_produto')
            ->orderBy('tb1_id')
            ->get(['tb1_id', 'matriz_id'])
            ->groupBy('matriz_id')
            ->each(function ($products) {
                foreach ($products as $product) {
                    DB::table('tb1_produto')
                        ->where('tb1_id', $product->tb1_id)
                        ->update([
                            'produto_id' => (int) $product->tb1_id,
                        ]);
                }
            });

        Schema::table('tb1_produto', function (Blueprint $table) {
            $table->unique(['matriz_id', 'produto_id'], 'tb1_produto_matriz_produto_unique');
            $table->index(['matriz_id', 'tb1_nome'], 'tb1_produto_matriz_nome_idx');
        });

        try {
            Schema::table('tb1_produto', function (Blueprint $table) {
                $table->dropUnique('tb1_produto_tb1_codbar_unique');
            });
        } catch (\Throwable $exception) {
            // O nome do indice pode variar em bancos existentes.
        }

        try {
            DB::statement('ALTER TABLE tb1_produto DROP INDEX tb1_produto_tb1_codbar_unique');
        } catch (\Throwable $exception) {
            // Ignora quando o indice nao existe com esse nome.
        }

        Schema::table('tb1_produto', function (Blueprint $table) {
            $table->unique(['matriz_id', 'tb1_codbar'], 'tb1_produto_matriz_codbar_unique');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('tb1_produto', function (Blueprint $table) {
                $table->dropUnique('tb1_produto_matriz_codbar_unique');
            });
        } catch (\Throwable $exception) {
        }

        try {
            Schema::table('tb1_produto', function (Blueprint $table) {
                $table->unique('tb1_codbar');
            });
        } catch (\Throwable $exception) {
        }

        Schema::table('tb1_produto', function (Blueprint $table) {
            $table->dropUnique('tb1_produto_matriz_produto_unique');
            $table->dropIndex('tb1_produto_matriz_nome_idx');
            $table->dropConstrainedForeignId('matriz_id');
            $table->dropColumn('produto_id');
        });
    }
};

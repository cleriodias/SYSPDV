<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb2_unidades', function (Blueprint $table) {
            $table->foreignId('matriz_id')
                ->nullable()
                ->after('tb2_id')
                ->constrained('matrizes')
                ->cascadeOnDelete();
            $table->string('tb2_tipo', 20)->default('filial')->after('matriz_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('matriz_id')
                ->nullable()
                ->after('tb2_id')
                ->constrained('matrizes')
                ->nullOnDelete();
        });

        $defaultMatrixId = DB::table('matrizes')->insertGetId([
            'nome' => 'Matriz Padrao',
            'slug' => Str::slug('Matriz Padrao'),
            'cnpj' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tb2_unidades')->orderBy('tb2_id')->get(['tb2_id'])->each(function ($unit, $index) use ($defaultMatrixId) {
            DB::table('tb2_unidades')
                ->where('tb2_id', $unit->tb2_id)
                ->update([
                    'matriz_id' => $defaultMatrixId,
                    'tb2_tipo' => $index === 0 ? 'matriz' : 'filial',
                ]);
        });

        DB::table('users')
            ->whereNotNull('tb2_id')
            ->update([
                'matriz_id' => $defaultMatrixId,
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matriz_id');
        });

        Schema::table('tb2_unidades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matriz_id');
            $table->dropColumn('tb2_tipo');
        });
    }
};

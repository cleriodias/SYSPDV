<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb28_aplicacoes', function (Blueprint $table) {
            $table->bigIncrements('tb28_id');
            $table->string('tb28_nome');
            $table->string('tb28_slug', 100)->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('tb28_aplicacoes')->insert([
            [
                'tb28_id' => 1,
                'tb28_nome' => 'Padaria + NFe',
                'tb28_slug' => 'padaria-nfe',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tb28_id' => 2,
                'tb28_nome' => 'Padaria',
                'tb28_slug' => 'padaria',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tb28_id' => 3,
                'tb28_nome' => 'NFe',
                'tb28_slug' => 'nfe',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tb28_aplicacoes');
    }
};

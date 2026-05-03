<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb30_nfe_produtos_seguro', function (Blueprint $table) {
            $table->unsignedBigInteger('tb31_id')
                ->nullable()
                ->after('tb2_id');

            $table->foreign('tb31_id')
                ->references('tb31_id')
                ->on('tb31_nfe_seguradoras')
                ->nullOnDelete();

            $table->index('tb31_id');
        });
    }

    public function down(): void
    {
        Schema::table('tb30_nfe_produtos_seguro', function (Blueprint $table) {
            $table->dropForeign(['tb31_id']);
            $table->dropIndex(['tb31_id']);
            $table->dropColumn('tb31_id');
        });
    }
};

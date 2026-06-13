<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tb33_ifood_configuracoes', function (Blueprint $table) {
            $table->id('tb33_id');
            $table->unsignedBigInteger('tb2_id')->unique();
            $table->boolean('tb33_ativo')->default(false);
            $table->string('tb33_ambiente', 20)->default('homologacao');
            $table->string('tb33_nome_loja', 120)->nullable();
            $table->string('tb33_merchant_id', 120)->nullable();
            $table->string('tb33_client_id', 120)->nullable();
            $table->text('tb33_client_secret')->nullable();
            $table->text('tb33_authorization_code')->nullable();
            $table->string('tb33_webhook_token', 120)->nullable();
            $table->text('tb33_observacoes')->nullable();
            $table->timestamps();

            $table->foreign('tb2_id')
                ->references('tb2_id')
                ->on('tb2_unidades')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb33_ifood_configuracoes');
    }
};

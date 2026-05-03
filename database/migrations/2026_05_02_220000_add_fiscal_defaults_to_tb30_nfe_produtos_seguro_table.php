<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb30_nfe_produtos_seguro', function (Blueprint $table) {
            $table->string('tb30_natureza_receita', 120)->default('premio de seguro');
            $table->string('tb30_ramo_fiscal', 120)->default('seguro de danos');
            $table->boolean('tb30_incide_iof')->default(true);
            $table->decimal('tb30_aliquota_iof', 5, 2)->default(7.38);
            $table->boolean('tb30_permite_override_iof')->default(true);
            $table->string('tb30_regra_base_iof', 160)->default('premio');
            $table->boolean('tb30_destacar_iof')->default(true);
            $table->boolean('tb30_ha_corretagem')->default(false);
            $table->boolean('tb30_gera_nfse')->default(false);
            $table->string('tb30_item_lista_servico', 20)->nullable();
            $table->string('tb30_codigo_servico_nfse', 30)->nullable();
            $table->string('tb30_municipio_iss', 120)->nullable();
            $table->string('tb30_uf_iss', 2)->nullable();
            $table->decimal('tb30_aliquota_iss', 5, 2)->default(0);
            $table->string('tb30_prestador_nfse', 160)->nullable();
            $table->string('tb30_tomador_nfse', 160)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tb30_nfe_produtos_seguro', function (Blueprint $table) {
            $table->dropColumn([
                'tb30_natureza_receita',
                'tb30_ramo_fiscal',
                'tb30_incide_iof',
                'tb30_aliquota_iof',
                'tb30_permite_override_iof',
                'tb30_regra_base_iof',
                'tb30_destacar_iof',
                'tb30_ha_corretagem',
                'tb30_gera_nfse',
                'tb30_item_lista_servico',
                'tb30_codigo_servico_nfse',
                'tb30_municipio_iss',
                'tb30_uf_iss',
                'tb30_aliquota_iss',
                'tb30_prestador_nfse',
                'tb30_tomador_nfse',
            ]);
        });
    }
};

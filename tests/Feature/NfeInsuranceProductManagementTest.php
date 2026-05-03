<?php

namespace Tests\Feature;

use App\Http\Controllers\NfeLaunchController;
use App\Models\Aplicacao;
use App\Models\Matriz;
use App\Models\NfeInsurer;
use App\Models\NfeInsuranceProduct;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Tests\TestCase;

class NfeInsuranceProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_item_inherits_iof_and_nfse_defaults_from_insurance_product(): void
    {
        $controller = new NfeLaunchController();
        $reflection = new ReflectionClass($controller);
        $normalizeItem = $reflection->getMethod('normalizeItem');
        $normalizeItem->setAccessible(true);

        $product = new NfeInsuranceProduct([
            'tb30_id' => 10,
            'tb30_codigo' => 'SEG-001',
            'tb30_nome' => 'Seguro Auto',
            'tb30_seguradora' => 'Seguradora XPTO',
            'tb30_ramo' => 'Auto',
            'tb30_modalidade' => 'Tradicional',
            'tb30_tipo_contratacao' => 'individual',
            'tb30_periodicidade' => 'mensal',
            'tb30_natureza_receita' => 'premio de seguro',
            'tb30_ramo_fiscal' => 'seguro de danos',
            'tb30_incide_iof' => true,
            'tb30_aliquota_iof' => 7.38,
            'tb30_permite_override_iof' => true,
            'tb30_regra_base_iof' => 'premio',
            'tb30_destacar_iof' => true,
            'tb30_ha_corretagem' => true,
            'tb30_gera_nfse' => true,
            'tb30_item_lista_servico' => '10.01',
            'tb30_codigo_servico_nfse' => 'SRV-1001',
            'tb30_municipio_iss' => 'Goiania',
            'tb30_uf_iss' => 'GO',
            'tb30_aliquota_iss' => 5,
            'tb30_prestador_nfse' => 'Corretora XPTO',
            'tb30_tomador_nfse' => 'Cliente XPTO',
            'tb30_cfop' => '',
            'tb30_ncm' => null,
            'tb30_unidade_padrao' => 'UN',
            'tb30_premio_base' => 199.9,
        ]);

        $items = new Collection([
            10 => $product,
        ]);

        $normalized = $normalizeItem->invoke($controller, [
            'produto_seguro_id' => 10,
            'quantidade' => 1,
            'valor_unitario' => 199.9,
            'desconto' => 0,
        ], $items, 0);

        $this->assertSame('premio de seguro', $normalized['natureza_receita']);
        $this->assertSame('seguro de danos', $normalized['ramo_fiscal']);
        $this->assertTrue($normalized['incide_iof']);
        $this->assertSame(7.38, $normalized['aliquota_iof']);
        $this->assertTrue($normalized['gera_nfse']);
        $this->assertSame('10.01', $normalized['item_lista_servico']);
        $this->assertSame('Goiania', $normalized['municipio_iss']);
        $this->assertSame('GO', $normalized['uf_iss']);
    }

    public function test_build_pendencias_does_not_require_cfop_but_requires_iof_rate(): void
    {
        $controller = new NfeLaunchController();
        $reflection = new ReflectionClass($controller);
        $buildPendencias = $reflection->getMethod('buildPendencias');
        $buildPendencias->setAccessible(true);

        $matrix = Matriz::create([
            'nome' => 'Matriz NFe',
            'slug' => Str::slug('Matriz NFe-' . fake()->unique()->numerify('###')),
            'status' => 1,
            'pagamento_ativo' => true,
        ]);

        $unit = Unidade::create([
            'tb2_nome' => 'Loja NFe',
            'matriz_id' => $matrix->id,
            'tb2_tipo' => 'matriz',
            'tb2_endereco' => 'Endereco Loja NFe',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(62) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_localizacao' => 'https://maps.example.com/loja-nfe',
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);

        $pendencias = $buildPendencias->invoke(
            $controller,
            ['tipo_pessoa' => 'pf', 'documento' => '12345678901'],
            ['indicador_presenca' => 'presencial'],
            [[
                'seguradora' => 'Seguradora XPTO',
                'ramo' => 'Auto',
                'natureza_receita' => 'premio de seguro',
                'incide_iof' => true,
                'aliquota_iof' => null,
                'regra_base_iof' => 'premio',
                'ha_corretagem' => false,
                'gera_nfse' => false,
                'cfop' => '',
            ]],
            ['forma_pagamento' => 'pix'],
            $unit
        );

        $messages = collect($pendencias)->pluck('message')->all();

        $this->assertContains('O item 1 esta sem aliquota de IOF.', $messages);
        $this->assertNotContains('O item 1 esta sem CFOP.', $messages);
    }

    public function test_store_product_uses_auxiliary_insurer_and_defaults_nfse_to_enabled(): void
    {
        $matrix = Matriz::create([
            'nome' => 'Matriz Produto Seguro',
            'slug' => Str::slug('Matriz Produto Seguro-' . fake()->unique()->numerify('###')),
            'tb28_id' => Aplicacao::NFE,
            'status' => 1,
            'pagamento_ativo' => true,
        ]);

        $unit = Unidade::create([
            'tb2_nome' => 'Unidade Produto Seguro',
            'matriz_id' => $matrix->id,
            'tb2_tipo' => 'matriz',
            'tb2_endereco' => 'Endereco Unidade Produto Seguro',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_localizacao' => 'https://maps.example.com/unidade-produto-seguro',
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Master Produto Seguro',
            'email' => 'master.produto.seguro@example.com',
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
        ]);
        $user->units()->sync([$unit->tb2_id]);

        $insurer = NfeInsurer::create([
            'matriz_id' => $matrix->id,
            'tb31_nome_fantasia' => 'Porto Seguro',
            'tb31_razao_social' => 'Porto Seguro Companhia',
            'tb31_status' => 1,
            'tb31_usa_integracao' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('nfe.insurance-products.store'), [
                'tb2_id' => $unit->tb2_id,
                'tb31_id' => $insurer->tb31_id,
                'tb30_codigo' => 'SEG-001',
                'tb30_nome' => 'Seguro Auto',
                'tb30_ramo' => 'Auto',
                'tb30_modalidade' => 'Tradicional',
                'tb30_tipo_contratacao' => 'individual',
                'tb30_periodicidade' => 'mensal',
                'tb30_natureza_receita' => 'premio de seguro',
                'tb30_ramo_fiscal' => 'seguro de danos',
                'tb30_incide_iof' => '1',
                'tb30_aliquota_iof' => '7.38',
                'tb30_permite_override_iof' => '1',
                'tb30_regra_base_iof' => 'premio',
                'tb30_destacar_iof' => '1',
                'tb30_ha_corretagem' => '1',
                'tb30_item_lista_servico' => '10.01',
                'tb30_codigo_servico_nfse' => '100102',
                'tb30_municipio_iss' => 'Brasilia',
                'tb30_uf_iss' => 'DF',
                'tb30_codigo_ibge_iss' => '5300108',
                'tb30_aliquota_iss' => '5.00',
                'tb30_prestador_nfse' => 'Corretora XPTO',
                'tb30_tomador_nfse' => 'Seguradora XPTO',
                'tb30_cfop' => '',
                'tb30_ncm' => '',
                'tb30_unidade_padrao' => 'UN',
                'tb30_premio_base' => '100.00',
                'tb30_comissao_percentual' => '10.00',
                'tb30_regras' => 'Regra teste',
                'tb30_status' => '1',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tb30_nfe_produtos_seguro', [
            'matriz_id' => $matrix->id,
            'tb2_id' => $unit->tb2_id,
            'tb31_id' => $insurer->tb31_id,
            'tb30_seguradora' => 'Porto Seguro',
            'tb30_gera_nfse' => 1,
            'tb30_codigo_ibge_iss' => '5300108',
        ]);
    }

    public function test_store_product_allows_blank_nfse_tomador_on_product_template(): void
    {
        $matrix = Matriz::create([
            'nome' => 'Matriz Produto Sem Tomador',
            'slug' => Str::slug('Matriz Produto Sem Tomador-' . fake()->unique()->numerify('###')),
            'tb28_id' => Aplicacao::NFE,
            'status' => 1,
            'pagamento_ativo' => true,
        ]);

        $unit = Unidade::create([
            'tb2_nome' => 'Unidade Produto Sem Tomador',
            'matriz_id' => $matrix->id,
            'tb2_tipo' => 'matriz',
            'tb2_endereco' => 'Endereco Unidade Produto Sem Tomador',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_localizacao' => 'https://maps.example.com/unidade-produto-sem-tomador',
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Master Produto Sem Tomador',
            'email' => 'master.produto.sem.tomador@example.com',
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
        ]);
        $user->units()->sync([$unit->tb2_id]);

        $insurer = NfeInsurer::create([
            'matriz_id' => $matrix->id,
            'tb31_nome_fantasia' => 'Allianz',
            'tb31_razao_social' => 'Allianz Companhia',
            'tb31_status' => 1,
            'tb31_usa_integracao' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('nfe.insurance-products.store'), [
                'tb2_id' => $unit->tb2_id,
                'tb31_id' => $insurer->tb31_id,
                'tb30_codigo' => 'SEG-002',
                'tb30_nome' => 'Seguro Residencial',
                'tb30_ramo' => 'Residencial',
                'tb30_modalidade' => 'Padrao',
                'tb30_tipo_contratacao' => 'individual',
                'tb30_periodicidade' => 'mensal',
                'tb30_natureza_receita' => 'premio de seguro',
                'tb30_ramo_fiscal' => 'seguro de danos',
                'tb30_incide_iof' => '1',
                'tb30_aliquota_iof' => '7.38',
                'tb30_permite_override_iof' => '1',
                'tb30_regra_base_iof' => 'premio',
                'tb30_destacar_iof' => '1',
                'tb30_ha_corretagem' => '1',
                'tb30_item_lista_servico' => '10.01',
                'tb30_codigo_servico_nfse' => '100102',
                'tb30_municipio_iss' => 'Brasilia',
                'tb30_uf_iss' => 'DF',
                'tb30_codigo_ibge_iss' => '5300108',
                'tb30_aliquota_iss' => '5.00',
                'tb30_prestador_nfse' => 'Corretora XPTO',
                'tb30_tomador_nfse' => '',
                'tb30_cfop' => '',
                'tb30_ncm' => '',
                'tb30_unidade_padrao' => 'UN',
                'tb30_premio_base' => '150.00',
                'tb30_comissao_percentual' => '12.00',
                'tb30_regras' => 'Produto sem tomador padrao',
                'tb30_status' => '1',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tb30_nfe_produtos_seguro', [
            'matriz_id' => $matrix->id,
            'tb31_id' => $insurer->tb31_id,
            'tb30_codigo' => 'SEG-002',
            'tb30_prestador_nfse' => 'Corretora XPTO',
            'tb30_tomador_nfse' => null,
        ]);
    }
}

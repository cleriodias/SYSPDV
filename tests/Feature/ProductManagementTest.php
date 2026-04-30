<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Models\Matriz;
use App\Models\Produto;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionClass;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_product_data_uses_product_id_as_barcode_for_new_balance_product(): void
    {
        $controller = new ProductController();
        $reflection = new ReflectionClass($controller);
        $prepareProductData = $reflection->getMethod('prepareProductData');
        $prepareProductData->setAccessible(true);

        $product = new Produto([
            'tb1_id' => 30,
            'tb1_codbar' => '',
            'tb1_tipo' => 1,
        ]);

        $result = $prepareProductData->invoke($controller, [
            'tb1_id' => 30,
            'tb1_nome' => 'Biscoito de Queijo',
            'tb1_tipo' => 1,
        ], $product);

        $this->assertSame('30', $result['tb1_codbar']);
    }

    public function test_prepare_product_data_keeps_product_id_as_barcode_for_existing_balance_product(): void
    {
        $controller = new ProductController();
        $reflection = new ReflectionClass($controller);
        $prepareProductData = $reflection->getMethod('prepareProductData');
        $prepareProductData->setAccessible(true);

        $product = new Produto([
            'tb1_id' => 30,
            'tb1_nome' => 'Biscoito de Queijo',
            'tb1_vlr_custo' => 3,
            'tb1_vlr_venda' => 4,
            'tb1_codbar' => '30',
            'tb1_tipo' => 1,
            'tb1_status' => 1,
            'tb1_vr_credit' => true,
        ]);
        $product->exists = true;

        $result = $prepareProductData->invoke($controller, [
            'tb1_id' => 30,
            'tb1_nome' => 'Biscoito de Queijo Atualizado',
            'tb1_tipo' => 1,
        ], $product);

        $this->assertSame('30', $result['tb1_codbar']);
        $this->assertArrayNotHasKey('tb1_id', $result);
    }

    public function test_prepare_product_data_uses_product_id_as_barcode_when_product_has_no_barcode(): void
    {
        $controller = new ProductController();
        $reflection = new ReflectionClass($controller);
        $prepareProductData = $reflection->getMethod('prepareProductData');
        $prepareProductData->setAccessible(true);

        $result = $prepareProductData->invoke($controller, [
            'tb1_id' => 3200,
            'tb1_nome' => 'Pudim',
            'tb1_tipo' => 0,
            'sem_codigo_barras' => true,
            'tb1_codbar' => '',
        ]);

        $this->assertSame('3200', $result['tb1_codbar']);
        $this->assertArrayNotHasKey('sem_codigo_barras', $result);
    }

    public function test_prepare_product_data_keeps_informed_barcode_when_product_has_own_barcode(): void
    {
        $controller = new ProductController();
        $reflection = new ReflectionClass($controller);
        $prepareProductData = $reflection->getMethod('prepareProductData');
        $prepareProductData->setAccessible(true);

        $result = $prepareProductData->invoke($controller, [
            'tb1_id' => 3201,
            'tb1_nome' => 'Bolo',
            'tb1_tipo' => 0,
            'sem_codigo_barras' => false,
            'tb1_codbar' => '7891234567890',
        ]);

        $this->assertSame('7891234567890', $result['tb1_codbar']);
        $this->assertArrayNotHasKey('sem_codigo_barras', $result);
    }

    public function test_sub_manager_can_change_product_prices(): void
    {
        $controller = new ProductController();
        $reflection = new ReflectionClass($controller);
        $ensurePriceEditingIsAuthorized = $reflection->getMethod('ensurePriceEditingIsAuthorized');
        $ensurePriceEditingIsAuthorized->setAccessible(true);

        $product = new Produto([
            'tb1_vlr_custo' => 3,
            'tb1_vlr_venda' => 5,
        ]);

        $user = new User([
            'funcao' => 2,
            'funcao_original' => 2,
        ]);

        $ensurePriceEditingIsAuthorized->invoke($controller, [
            'tb1_vlr_custo' => 4,
            'tb1_vlr_venda' => 6,
        ], $product, $user);

        $this->assertTrue(true);
    }

    public function test_cashier_cannot_change_product_prices(): void
    {
        $controller = new ProductController();
        $reflection = new ReflectionClass($controller);
        $ensurePriceEditingIsAuthorized = $reflection->getMethod('ensurePriceEditingIsAuthorized');
        $ensurePriceEditingIsAuthorized->setAccessible(true);

        $product = new Produto([
            'tb1_vlr_custo' => 3,
            'tb1_vlr_venda' => 5,
        ]);

        $user = new User([
            'funcao' => 3,
            'funcao_original' => 3,
        ]);

        $this->expectException(ValidationException::class);

        try {
            $ensurePriceEditingIsAuthorized->invoke($controller, [
                'tb1_vlr_custo' => 4,
                'tb1_vlr_venda' => 6,
            ], $product, $user);
        } catch (\ReflectionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $previous = $exception->getPrevious();

            if ($previous instanceof ValidationException) {
                $this->assertSame(
                    'Apenas Master, Gerente e Sub-Gerente podem alterar o valor de custo.',
                    $previous->errors()['tb1_vlr_custo'][0] ?? null
                );
                $this->assertSame(
                    'Apenas Master, Gerente e Sub-Gerente podem alterar o valor de venda.',
                    $previous->errors()['tb1_vlr_venda'][0] ?? null
                );
            }

            throw $previous ?? $exception;
        }
    }

    public function test_services_catalog_lists_only_service_items_from_the_active_matrix(): void
    {
        $matrix = Matriz::create([
            'nome' => 'Matriz Servicos',
            'slug' => Str::slug('Matriz Servicos-' . fake()->unique()->numerify('###')),
            'status' => 1,
            'pagamento_ativo' => true,
        ]);

        $unit = Unidade::create([
            'tb2_nome' => 'Loja Servicos',
            'matriz_id' => $matrix->id,
            'tb2_tipo' => 'matriz',
            'tb2_endereco' => 'Endereco Loja Servicos',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_localizacao' => 'https://maps.example.com/loja-servicos',
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);

        $user = User::factory()->create([
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
        ]);
        $user->units()->sync([$unit->tb2_id]);

        Produto::create([
            'matriz_id' => $matrix->id,
            'produto_id' => 1,
            'tb1_nome' => 'CAFE TORRADO',
            'tb1_vlr_custo' => 8,
            'tb1_vlr_venda' => 12,
            'tb1_codbar' => '1001',
            'tb1_tipo' => 0,
            'tb1_status' => 1,
        ]);

        Produto::create([
            'matriz_id' => $matrix->id,
            'produto_id' => 2,
            'tb1_nome' => 'SERVICO DE ENTREGA',
            'tb1_vlr_custo' => 4,
            'tb1_vlr_venda' => 10,
            'tb1_codbar' => '2002',
            'tb1_tipo' => 2,
            'tb1_status' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_unit' => [
                    'id' => $unit->tb2_id,
                    'name' => $unit->tb2_nome,
                    'address' => $unit->tb2_endereco,
                    'cnpj' => $unit->tb2_cnpj,
                ],
            ])
            ->get('/products?catalog=services');

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Products/ProductIndex')
            ->where('catalogMode', 'services')
            ->has('products.data', 1)
            ->where('products.data.0.tb1_tipo', 2)
            ->where('products.data.0.tb1_nome', 'SERVICO DE ENTREGA')
        );
    }
}

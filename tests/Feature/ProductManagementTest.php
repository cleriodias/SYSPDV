<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Models\Matriz;
use App\Models\Produto;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_store_syncs_dashboard_quick_lookup_cache_for_all_units_of_matrix(): void
    {
        ['matrix' => $matrix, 'units' => $units, 'user' => $user] = $this->createProductContext();

        foreach ($units as $unit) {
            Cache::put($this->quickLookupCacheKey($unit->tb2_id), [
                [
                    'tb1_id' => 999,
                    'produto_id' => 999,
                    'tb1_nome' => 'CACHE ANTIGO',
                    'tb1_codbar' => '999',
                    'tb1_vlr_custo' => 1.0,
                    'tb1_vlr_venda' => 2.0,
                    'tb1_tipo' => 0,
                    'tb1_qtd' => 0,
                    'tb1_status' => 1,
                    'tb1_vr_credit' => false,
                ],
            ], now()->addHour());
        }

        $response = $this->actingAs($user)
            ->withSession($this->activeUnitSession($units[0]))
            ->post(route('products.store'), $this->productPayload([
                'tb1_nome' => 'Produto Cache Novo',
            ]));

        $product = Produto::query()
            ->where('matriz_id', $matrix->id)
            ->latest('tb1_id')
            ->first();

        $response->assertRedirect(route('products.show', ['product' => $product->tb1_id]));

        foreach ($units as $unit) {
            $cachedProducts = Cache::get($this->quickLookupCacheKey($unit->tb2_id), []);

            $this->assertSame($product->tb1_id, $cachedProducts[0]['tb1_id'] ?? null);
            $this->assertSame($product->produto_id, $cachedProducts[0]['produto_id'] ?? null);
            $this->assertSame($product->tb1_nome, $cachedProducts[0]['tb1_nome'] ?? null);
        }
    }

    public function test_update_syncs_dashboard_quick_lookup_cache_with_new_product_data(): void
    {
        ['matrix' => $matrix, 'units' => $units, 'user' => $user] = $this->createProductContext();

        $product = Produto::create([
            'matriz_id' => $matrix->id,
            'produto_id' => 41,
            'tb1_nome' => 'PRODUTO ANTIGO',
            'tb1_vlr_custo' => 5,
            'tb1_vlr_venda' => 7,
            'tb1_codbar' => '41',
            'tb1_tipo' => 0,
            'tb1_status' => 1,
            'tb1_vr_credit' => false,
        ]);

        foreach ($units as $unit) {
            Cache::put($this->quickLookupCacheKey($unit->tb2_id), [
                [
                    'tb1_id' => $product->tb1_id,
                    'produto_id' => $product->produto_id,
                    'tb1_nome' => 'PRODUTO DESATUALIZADO',
                    'tb1_codbar' => '41',
                    'tb1_vlr_custo' => 1.0,
                    'tb1_vlr_venda' => 2.0,
                    'tb1_tipo' => 0,
                    'tb1_qtd' => 0,
                    'tb1_status' => 1,
                    'tb1_vr_credit' => false,
                ],
            ], now()->addHour());
        }

        $response = $this->actingAs($user)
            ->withSession($this->activeUnitSession($units[0]))
            ->put(route('products.update', ['product' => $product->tb1_id]), $this->productPayload([
                'tb1_nome' => 'Produto Atualizado',
                'tb1_vlr_custo' => 8,
                'tb1_vlr_venda' => 11,
            ]));

        $product->refresh();

        $response->assertRedirect(route('products.show', ['product' => $product->tb1_id]));

        foreach ($units as $unit) {
            $cachedProducts = Cache::get($this->quickLookupCacheKey($unit->tb2_id), []);

            $this->assertSame($product->tb1_id, $cachedProducts[0]['tb1_id'] ?? null);
            $this->assertSame($product->tb1_nome, $cachedProducts[0]['tb1_nome'] ?? null);
            $this->assertSame($product->tb1_vlr_custo, $cachedProducts[0]['tb1_vlr_custo'] ?? null);
            $this->assertSame($product->tb1_vlr_venda, $cachedProducts[0]['tb1_vlr_venda'] ?? null);
        }
    }

    public function test_update_removes_inactive_product_from_dashboard_quick_lookup_cache(): void
    {
        ['matrix' => $matrix, 'units' => $units, 'user' => $user] = $this->createProductContext();

        $product = Produto::create([
            'matriz_id' => $matrix->id,
            'produto_id' => 55,
            'tb1_nome' => 'PRODUTO ATIVO',
            'tb1_vlr_custo' => 4,
            'tb1_vlr_venda' => 6,
            'tb1_codbar' => '55',
            'tb1_tipo' => 0,
            'tb1_status' => 1,
            'tb1_vr_credit' => false,
        ]);

        foreach ($units as $unit) {
            Cache::put($this->quickLookupCacheKey($unit->tb2_id), [
                [
                    'tb1_id' => $product->tb1_id,
                    'produto_id' => $product->produto_id,
                    'tb1_nome' => $product->tb1_nome,
                    'tb1_codbar' => $product->tb1_codbar,
                    'tb1_vlr_custo' => $product->tb1_vlr_custo,
                    'tb1_vlr_venda' => $product->tb1_vlr_venda,
                    'tb1_tipo' => $product->tb1_tipo,
                    'tb1_qtd' => 0,
                    'tb1_status' => 1,
                    'tb1_vr_credit' => false,
                ],
                [
                    'tb1_id' => 888,
                    'produto_id' => 888,
                    'tb1_nome' => 'PRODUTO RESERVA',
                    'tb1_codbar' => '888',
                    'tb1_vlr_custo' => 2.0,
                    'tb1_vlr_venda' => 3.0,
                    'tb1_tipo' => 0,
                    'tb1_qtd' => 0,
                    'tb1_status' => 1,
                    'tb1_vr_credit' => false,
                ],
            ], now()->addHour());
        }

        $response = $this->actingAs($user)
            ->withSession($this->activeUnitSession($units[0]))
            ->put(route('products.update', ['product' => $product->tb1_id]), $this->productPayload([
                'tb1_nome' => $product->tb1_nome,
                'tb1_vlr_custo' => $product->tb1_vlr_custo,
                'tb1_vlr_venda' => $product->tb1_vlr_venda,
                'tb1_status' => 0,
                'tb1_codbar' => $product->tb1_codbar,
                'sem_codigo_barras' => false,
            ]));

        $response->assertRedirect(route('products.show', ['product' => $product->tb1_id]));

        foreach ($units as $unit) {
            $cachedProducts = Cache::get($this->quickLookupCacheKey($unit->tb2_id), []);

            $this->assertCount(1, $cachedProducts);
            $this->assertSame(888, $cachedProducts[0]['tb1_id'] ?? null);
        }
    }

    private function createProductContext(): array
    {
        $matrix = Matriz::create([
            'nome' => 'Matriz Cache Produtos',
            'slug' => Str::slug('Matriz Cache Produtos-' . fake()->unique()->numerify('###')),
            'status' => 1,
            'pagamento_ativo' => true,
        ]);

        $unitA = Unidade::create([
            'tb2_nome' => 'Loja Cache A',
            'matriz_id' => $matrix->id,
            'tb2_tipo' => 'matriz',
            'tb2_endereco' => 'Endereco Loja Cache A',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000111',
            'tb2_localizacao' => 'https://maps.example.com/loja-cache-a',
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);

        $unitB = Unidade::create([
            'tb2_nome' => 'Loja Cache B',
            'matriz_id' => $matrix->id,
            'tb2_tipo' => 'filial',
            'tb2_endereco' => 'Endereco Loja Cache B',
            'tb2_cep' => '72900-001',
            'tb2_fone' => '(61) 98888-8888',
            'tb2_cnpj' => '12345678000112',
            'tb2_localizacao' => 'https://maps.example.com/loja-cache-b',
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);

        $user = User::factory()->create([
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unitA->tb2_id,
            'matriz_id' => $matrix->id,
        ]);
        $user->units()->sync([$unitA->tb2_id, $unitB->tb2_id]);

        return [
            'matrix' => $matrix,
            'units' => [$unitA, $unitB],
            'user' => $user,
        ];
    }

    private function activeUnitSession(Unidade $unit): array
    {
        return [
            'active_unit' => [
                'id' => $unit->tb2_id,
                'name' => $unit->tb2_nome,
                'address' => $unit->tb2_endereco,
                'cnpj' => $unit->tb2_cnpj,
            ],
        ];
    }

    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'tb1_nome' => 'Produto Base',
            'tb1_vlr_custo' => 5,
            'tb1_vlr_venda' => 8,
            'tb1_codbar' => '',
            'tb1_tipo' => 0,
            'tb1_status' => 1,
            'tb1_vr_credit' => false,
            'sem_codigo_barras' => true,
        ], $overrides);
    }

    private function quickLookupCacheKey(int $unitId): string
    {
        return sprintf('dashboard:quick-products:v1:unit:%d', $unitId);
    }
}

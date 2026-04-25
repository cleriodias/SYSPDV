<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Produto;
use App\Models\Unidade;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanchoneteComandaRemovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_removing_comanda_item_requires_reason(): void
    {
        $unit = $this->makeUnit('Loja Lanchonete');
        $authenticatedUser = $this->makeUser('Operador Terminal', 4, 4, $unit, 'LANCH1');
        $this->makeProduct(12, 'Coxinha', 8.50);

        $sale = Venda::create([
            'tb1_id' => 12,
            'id_comanda' => 3000,
            'produto_nome' => 'Coxinha',
            'valor_unitario' => 8.50,
            'quantidade' => 1,
            'valor_total' => 8.50,
            'data_hora' => now(),
            'id_user_caixa' => null,
            'id_user_vale' => null,
            'id_lanc' => $authenticatedUser->id,
            'id_unidade' => $unit->tb2_id,
            'tipo_pago' => 'faturar',
            'status_pago' => false,
            'status' => 0,
        ]);

        $response = $this
            ->actingAs($authenticatedUser)
            ->withSession($this->activeSessionPayload($unit, 4))
            ->putJson(route('sales.comandas.update-item', [
                'codigo' => 3000,
                'productId' => 'product-12-price-8.50',
            ]), [
                'quantity' => 0,
                'access_user_id' => $authenticatedUser->id,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['removal_reason']);

        $this->assertDatabaseHas('tb3_vendas', [
            'id' => $sale->id,
        ]);
    }

    public function test_removing_comanda_item_sends_system_chat_to_master_manager_and_executor(): void
    {
        $unit = $this->makeUnit('Loja Lanchonete');
        $authenticatedUser = $this->makeUser('Operador Terminal', 4, 4, $unit, 'AUTH1');
        $executor = $this->makeUser('Executor Lanchonete', 4, 4, $unit, 'LANCH2');
        $master = $this->makeUser('Master', 0, 0, $unit, 'MSTR1');
        $manager = $this->makeUser('Gerente Loja', 1, 1, $unit, 'GER01');
        $this->makeProduct(12, 'Pastel', 10.25);

        $sale = Venda::create([
            'tb1_id' => 12,
            'id_comanda' => 3000,
            'produto_nome' => 'Pastel',
            'valor_unitario' => 10.25,
            'quantidade' => 2,
            'valor_total' => 20.50,
            'data_hora' => now(),
            'id_user_caixa' => null,
            'id_user_vale' => null,
            'id_lanc' => $executor->id,
            'id_unidade' => $unit->tb2_id,
            'tipo_pago' => 'faturar',
            'status_pago' => false,
            'status' => 0,
        ]);

        $response = $this
            ->actingAs($authenticatedUser)
            ->withSession($this->activeSessionPayload($unit, 4))
            ->putJson(route('sales.comandas.update-item', [
                'codigo' => 3000,
                'productId' => 'product-12-price-10.25',
            ]), [
                'quantity' => 0,
                'access_user_id' => $executor->id,
                'removal_reason' => 'Cliente desistiu do pedido',
            ]);

        $response->assertOk();

        $this->assertDatabaseMissing('tb3_vendas', [
            'id' => $sale->id,
        ]);

        $systemUser = User::query()->where('email', 'sistema.chat@pec.local')->first();

        $this->assertNotNull($systemUser);

        foreach ([$master->id, $manager->id, $executor->id] as $recipientId) {
            $this->assertDatabaseHas('tb22_chat_mensagens', [
                'sender_id' => $systemUser->id,
                'recipient_id' => $recipientId,
                'sender_unit_id' => $unit->tb2_id,
            ]);
        }

        $message = ChatMessage::query()
            ->where('sender_id', $systemUser->id)
            ->where('recipient_id', $master->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($message);
        $this->assertStringContainsString('Aviso automatico do sistema - exclusao de item da comanda', $message->message);
        $this->assertStringContainsString('Registro gerado automaticamente pelo terminal da lanchonete.', $message->message);
        $this->assertStringContainsString('Responsavel pela exclusao: Executor Lanchonete', $message->message);
        $this->assertStringContainsString('Cliente desistiu do pedido', $message->message);
        $this->assertStringContainsString('Pastel', $message->message);
    }

    private function makeUnit(string $name): Unidade
    {
        return Unidade::create([
            'tb2_nome' => $name,
            'tb2_endereco' => 'Endereco ' . $name,
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => fake()->unique()->numerify('##.###.###/####-##'),
            'tb2_localizacao' => 'https://maps.example.com/' . fake()->slug(),
            'tb2_status' => 1,
        ]);
    }

    private function makeProduct(int $id, string $name, float $salePrice): Produto
    {
        return Produto::create([
            'tb1_id' => $id,
            'produto_id' => $id,
            'tb1_nome' => $name,
            'tb1_vlr_custo' => 0,
            'tb1_vlr_venda' => $salePrice,
            'tb1_codbar' => fake()->unique()->numerify('#############'),
            'tb1_tipo' => 1,
            'tb1_status' => 1,
            'tb1_vr_credit' => false,
        ]);
    }

    private function makeUser(
        string $name,
        int $role,
        int $originalRole,
        Unidade $unit,
        string $accessCode,
        array $allowedUnits = [],
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'funcao' => $role,
            'funcao_original' => $originalRole,
            'tb2_id' => $unit->tb2_id,
            'cod_acesso' => $accessCode,
        ]);

        $unitIds = collect($allowedUnits)
            ->prepend($unit)
            ->map(fn ($value) => $value instanceof Unidade ? $value->tb2_id : (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $user->units()->sync($unitIds);

        return $user;
    }

    private function activeSessionPayload(Unidade $unit, int $role): array
    {
        return [
            'active_unit' => [
                'id' => $unit->tb2_id,
                'name' => $unit->tb2_nome,
                'address' => $unit->tb2_endereco,
                'cnpj' => $unit->tb2_cnpj,
            ],
            'active_role' => $role,
        ];
    }
}

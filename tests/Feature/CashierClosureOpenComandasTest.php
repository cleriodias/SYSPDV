<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\CashierClosure;
use App\Models\Matriz;
use App\Models\Produto;
use App\Models\Unidade;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashierClosureOpenComandasTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_close_requires_observation_when_open_comandas_exist(): void
    {
        $matrix = $this->makeMatrix('Matriz Alpha');
        $unit = $this->makeUnit('Loja Alpha', $matrix);
        $cashier = $this->makeUser('Caixa Alpha', 3, $unit, $matrix, 'CX001');
        $this->makeProduct(12, 'Coxinha', 8.50, $matrix);
        $this->makeOpenComanda($cashier, $unit, 3000, 12, 'Coxinha', 8.50, 1);

        $response = $this
            ->actingAs($cashier)
            ->withSession($this->activeSessionPayload($unit, 3))
            ->post(route('cashier.close.store'), [
                'cash_amount' => 100,
                'card_amount' => 50,
            ]);

        $response
            ->assertRedirect(route('cashier.close'))
            ->assertSessionHasErrors(['open_comandas_observation']);

        $this->assertDatabaseCount('cashier_closures', 0);
    }

    public function test_cashier_can_close_with_open_comandas_and_notifies_only_same_unit_same_matrix_management(): void
    {
        $matrixA = $this->makeMatrix('Matriz Alpha');
        $matrixB = $this->makeMatrix('Matriz Beta');
        $unitA = $this->makeUnit('Loja Alpha', $matrixA);
        $otherUnitSameMatrix = $this->makeUnit('Loja Alpha 2', $matrixA);
        $unitB = $this->makeUnit('Loja Beta', $matrixB);

        $cashier = $this->makeUser('Caixa Alpha', 3, $unitA, $matrixA, 'CX001');
        $masterSameUnit = $this->makeUser('Master Alpha', 0, $unitA, $matrixA, 'MA001');
        $managerSameUnit = $this->makeUser('Gerente Alpha', 1, $unitA, $matrixA, 'GA001');
        $masterOtherUnitSameMatrix = $this->makeUser('Master Alpha 2', 0, $otherUnitSameMatrix, $matrixA, 'MA002');
        $masterOtherMatrix = $this->makeUser('Master Beta', 0, $unitB, $matrixB, 'MB001');

        $this->makeProduct(12, 'Pastel', 10.25, $matrixA);
        $this->makeOpenComanda($cashier, $unitA, 3000, 12, 'Pastel', 10.25, 2);

        $response = $this
            ->actingAs($cashier)
            ->withSession($this->activeSessionPayload($unitA, 3))
            ->post(route('cashier.close.store'), [
                'cash_amount' => 100,
                'card_amount' => 50,
                'open_comandas_observation' => 'Caixa encerrado no fim do expediente com comanda ainda pendente.',
            ]);

        $response->assertRedirect(route('login'));

        $closure = CashierClosure::query()->latest('id')->first();

        $this->assertNotNull($closure);
        $this->assertSame('Caixa encerrado no fim do expediente com comanda ainda pendente.', $closure->open_comandas_observation);

        $systemUser = User::query()->where('email', 'sistema.chat@pec.local')->first();

        $this->assertNotNull($systemUser);

        $this->assertDatabaseHas('tb22_chat_mensagens', [
            'sender_id' => $systemUser->id,
            'recipient_id' => $masterSameUnit->id,
            'sender_unit_id' => $unitA->tb2_id,
        ]);

        $this->assertDatabaseHas('tb22_chat_mensagens', [
            'sender_id' => $systemUser->id,
            'recipient_id' => $managerSameUnit->id,
            'sender_unit_id' => $unitA->tb2_id,
        ]);

        $this->assertDatabaseMissing('tb22_chat_mensagens', [
            'sender_id' => $systemUser->id,
            'recipient_id' => $masterOtherUnitSameMatrix->id,
        ]);

        $this->assertDatabaseMissing('tb22_chat_mensagens', [
            'sender_id' => $systemUser->id,
            'recipient_id' => $masterOtherMatrix->id,
        ]);

        $message = ChatMessage::query()
            ->where('sender_id', $systemUser->id)
            ->where('recipient_id', $masterSameUnit->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($message);
        $this->assertStringContainsString('Fechamento de caixa com comandas em aberto', $message->message);
        $this->assertStringContainsString('Caixa Alpha', $message->message);
        $this->assertStringContainsString('Loja Alpha', $message->message);
        $this->assertStringContainsString('Pastel', $message->message);
        $this->assertStringContainsString('Caixa encerrado no fim do expediente com comanda ainda pendente.', $message->message);
    }

    private function makeMatrix(string $name): Matriz
    {
        return Matriz::create([
            'nome' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->numerify('###')),
            'status' => 1,
            'pagamento_ativo' => true,
        ]);
    }

    private function makeUnit(string $name, Matriz $matrix): Unidade
    {
        return Unidade::create([
            'tb2_nome' => $name,
            'matriz_id' => $matrix->id,
            'tb2_tipo' => 'filial',
            'tb2_endereco' => 'Endereco ' . $name,
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => fake()->unique()->numerify('##.###.###/####-##'),
            'tb2_localizacao' => 'https://maps.example.com/' . fake()->slug(),
            'tb2_status' => 1,
        ]);
    }

    private function makeUser(string $name, int $role, Unidade $unit, Matriz $matrix, string $accessCode): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'funcao' => $role,
            'funcao_original' => $role,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
            'cod_acesso' => $accessCode,
        ]);

        $user->units()->sync([$unit->tb2_id]);

        return $user;
    }

    private function makeProduct(int $id, string $name, float $salePrice, Matriz $matrix): Produto
    {
        return Produto::create([
            'tb1_id' => $id,
            'matriz_id' => $matrix->id,
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

    private function makeOpenComanda(
        User $cashier,
        Unidade $unit,
        int $comanda,
        int $productId,
        string $productName,
        float $unitPrice,
        int $quantity,
    ): Venda {
        return Venda::create([
            'tb1_id' => $productId,
            'id_comanda' => $comanda,
            'produto_nome' => $productName,
            'valor_unitario' => $unitPrice,
            'quantidade' => $quantity,
            'valor_total' => round($unitPrice * $quantity, 2),
            'data_hora' => now(),
            'id_user_caixa' => $cashier->id,
            'id_user_vale' => null,
            'id_lanc' => null,
            'id_unidade' => $unit->tb2_id,
            'tipo_pago' => 'faturar',
            'status_pago' => false,
            'status' => 0,
        ]);
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

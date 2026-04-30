<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Matriz;
use App\Models\OnlineUser;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnlineFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_lanchonete_only_sees_cashier_profiles_from_same_active_unit(): void
    {
        $matrix = $this->makeMatrix('Matriz Lanchonete');
        $unitA = $this->makeUnit('Loja A', $matrix, 'matriz');
        $unitB = $this->makeUnit('Loja B', $matrix);

        $viewer = $this->makeUser('Lanchonete', 4, $unitA);
        $sameUnitSubManager = $this->makeUser('SubCaixa', 2, $unitA);
        $sameUnitCashier = $this->makeUser('CaixaLoja', 3, $unitA);
        $sameUnitManager = $this->makeUser('GerenteLoja', 1, $unitA, [$unitA]);
        $otherUnitCashier = $this->makeUser('CaixaFora', 3, $unitB);
        $master = $this->makeUser('Master', 0, $unitB, [$unitA, $unitB]);

        $this->makePresence($sameUnitSubManager, 2, $unitA);
        $this->makePresence($sameUnitCashier, 3, $unitA);
        $this->makePresence($sameUnitManager, 1, $unitA);
        $this->makePresence($otherUnitCashier, 3, $unitB);
        $this->makePresence($master, 0, $unitB);

        $response = $this
            ->actingAs($viewer)
            ->withSession($this->activeSessionPayload($unitA, 4))
            ->get(route('online.snapshot'));

        $response->assertOk();

        $users = collect($response->json('onlineUsers'));

        $this->assertSame(
            [$sameUnitCashier->id, $sameUnitSubManager->id],
            $users->pluck('id')->all()
        );
    }

    public function test_cashier_sees_master_all_managers_and_same_unit_users(): void
    {
        $matrix = $this->makeMatrix('Matriz Caixa');
        $unitA = $this->makeUnit('Loja A', $matrix, 'matriz');
        $unitB = $this->makeUnit('Loja B', $matrix);

        $viewer = $this->makeUser('CaixaBase', 3, $unitA);
        $master = $this->makeUser('Master', 0, $unitB, [$unitA, $unitB]);
        $managerA = $this->makeUser('GerenteA', 1, $unitA, [$unitA]);
        $managerB = $this->makeUser('GerenteB', 1, $unitB, [$unitB]);
        $subManagerA = $this->makeUser('SubA', 2, $unitA);
        $lanchoneteA = $this->makeUser('LanchoneteA', 4, $unitA);
        $cashierB = $this->makeUser('CaixaB', 3, $unitB);

        $this->makePresence($master, 0, $unitB);
        $this->makePresence($managerA, 1, $unitA);
        $this->makePresence($managerB, 1, $unitB);
        $this->makePresence($subManagerA, 2, $unitA);
        $this->makePresence($lanchoneteA, 4, $unitA);
        $this->makePresence($cashierB, 3, $unitB);

        $response = $this
            ->actingAs($viewer)
            ->withSession($this->activeSessionPayload($unitA, 3))
            ->get(route('online.snapshot'));

        $response->assertOk();

        $users = collect($response->json('onlineUsers'));

        $this->assertTrue($users->pluck('id')->contains($master->id));
        $this->assertTrue($users->pluck('id')->contains($managerA->id));
        $this->assertTrue($users->pluck('id')->contains($managerB->id));
        $this->assertTrue($users->pluck('id')->contains($subManagerA->id));
        $this->assertTrue($users->pluck('id')->contains($lanchoneteA->id));
        $this->assertFalse($users->pluck('id')->contains($cashierB->id));
    }

    public function test_lanchonete_cannot_message_master_but_can_message_cashier_of_same_unit(): void
    {
        $matrix = $this->makeMatrix('Matriz Chat');
        $unitA = $this->makeUnit('Loja A', $matrix, 'matriz');
        $unitB = $this->makeUnit('Loja B', $matrix);

        $viewer = $this->makeUser('Lanchonete', 4, $unitA);
        $master = $this->makeUser('Master', 0, $unitB, [$unitA, $unitB]);
        $cashier = $this->makeUser('CaixaLoja', 3, $unitA);

        $this->makePresence($master, 0, $unitB);
        $this->makePresence($cashier, 3, $unitA);

        $session = $this->activeSessionPayload($unitA, 4);

        $this->actingAs($viewer)
            ->withSession($session)
            ->post(route('online.messages.store'), [
                'recipient_user_id' => $master->id,
                'message' => 'Mensagem nao permitida',
            ])
            ->assertSessionHasErrors();

        $response = $this->actingAs($viewer)
            ->withSession($session)
            ->post(route('online.messages.store'), [
                'recipient_user_id' => $cashier->id,
                'message' => '[b]Pode atender o caixa?[/b]',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('tb22_chat_mensagens', [
            'sender_id' => $viewer->id,
            'recipient_id' => $cashier->id,
            'message' => '[b]Pode atender o caixa?[/b]',
        ]);
    }

    public function test_master_cannot_see_or_message_users_from_other_matrix(): void
    {
        $matrixA = $this->makeMatrix('Matriz Alpha');
        $matrixB = $this->makeMatrix('Matriz Beta');
        $unitA = $this->makeUnit('Loja Alpha', $matrixA, 'matriz');
        $unitB = $this->makeUnit('Loja Beta', $matrixB, 'matriz');

        $viewer = $this->makeUser('Master Alpha', 0, $unitA, [$unitA]);
        $sameMatrixManager = $this->makeUser('Gerente Alpha', 1, $unitA, [$unitA]);
        $otherMatrixMaster = $this->makeUser('Master Beta', 0, $unitB, [$unitB]);

        $this->makePresence($sameMatrixManager, 1, $unitA);

        $response = $this
            ->actingAs($viewer)
            ->withSession($this->activeSessionPayload($unitA, 0))
            ->get(route('online.snapshot'));

        $response->assertOk();

        $onlineUsers = collect($response->json('onlineUsers'));
        $offlineUsers = collect($response->json('offlineUsers'));

        $this->assertTrue($onlineUsers->pluck('id')->contains($sameMatrixManager->id));
        $this->assertFalse($onlineUsers->pluck('id')->contains($otherMatrixMaster->id));
        $this->assertFalse($offlineUsers->pluck('id')->contains($otherMatrixMaster->id));

        $this->actingAs($viewer)
            ->withSession($this->activeSessionPayload($unitA, 0))
            ->post(route('online.messages.store'), [
                'recipient_user_id' => $otherMatrixMaster->id,
                'message' => 'Nao deveria sair da matriz',
            ])
            ->assertSessionHasErrors('recipient_user_id');

        $this->assertDatabaseMissing('tb22_chat_mensagens', [
            'sender_id' => $viewer->id,
            'recipient_id' => $otherMatrixMaster->id,
            'message' => 'Nao deveria sair da matriz',
        ]);
    }

    public function test_delegated_boss_as_master_only_sees_contacts_from_active_matrix(): void
    {
        $matrixA = $this->makeMatrix('Matriz Dash');
        $matrixB = $this->makeMatrix('Matriz Operacional');
        $unitA = $this->makeUnit('Dash', $matrixA, 'matriz');
        $unitB = $this->makeUnit('Loja Operacional', $matrixB, 'matriz');

        $delegatedBoss = User::factory()->create([
            'id' => 1,
            'name' => 'Boss Delegado',
            'email' => 'boss.delegado@example.com',
            'password' => Hash::make('1234'),
            'funcao' => 7,
            'funcao_original' => 7,
            'tb2_id' => $unitA->tb2_id,
            'matriz_id' => $unitA->matriz_id,
        ]);
        $delegatedBoss->units()->sync([$unitA->tb2_id, $unitB->tb2_id]);
        $masterA = $this->makeUser('Master Dash', 0, $unitA, [$unitA]);
        $masterB = $this->makeUser('Master Operacional', 0, $unitB, [$unitB]);

        $this->makePresence($masterA, 0, $unitA);
        $this->makePresence($masterB, 0, $unitB);

        $response = $this
            ->actingAs($delegatedBoss)
            ->withSession($this->activeSessionPayload($unitB, 0))
            ->get(route('online.snapshot'));

        $response->assertOk();

        $users = collect($response->json('onlineUsers'));

        $this->assertSame(0, (int) $response->json('currentUser.role'));
        $this->assertTrue($users->pluck('id')->contains($masterB->id));
        $this->assertFalse($users->pluck('id')->contains($masterA->id));
    }

    public function test_funcionario_and_cliente_profiles_cannot_log_in(): void
    {
        $unit = $this->makeUnit('Loja Login');

        foreach ([5 => 'funcionario', 6 => 'cliente'] as $role => $username) {
            $user = User::factory()->create([
                'name' => ucfirst($username),
                'email' => $username . '@paoecafe83.com.br',
                'password' => Hash::make('1234'),
                'funcao' => $role,
                'funcao_original' => $role,
                'tb2_id' => $unit->tb2_id,
                'matriz_id' => $unit->matriz_id,
            ]);
            $user->units()->sync([$unit->tb2_id]);

            $this->post(route('login'), [
                'email' => $user->email,
                'password' => '1234',
                'unit_id' => $unit->tb2_id,
            ])->assertSessionHasErrors('email');

            $this->assertGuest();
        }
    }

    public function test_sender_can_only_edit_unread_messages(): void
    {
        $unit = $this->makeUnit('Loja Edicao');
        $sender = $this->makeUser('Remetente', 3, $unit);
        $recipient = $this->makeUser('Destino', 2, $unit);

        $message = ChatMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'sender_role' => 3,
            'sender_unit_id' => $unit->tb2_id,
            'message' => 'Texto original',
        ]);

        $session = $this->activeSessionPayload($unit, 3);

        $this->actingAs($sender)
            ->withSession($session)
            ->put(route('online.messages.update', $message), [
                'message' => 'Texto ajustado',
            ])
            ->assertOk();

        $this->assertDatabaseHas('tb22_chat_mensagens', [
            'id' => $message->id,
            'message' => 'Texto ajustado',
        ]);

        $message->forceFill(['read_at' => now()])->save();

        $this->actingAs($sender)
            ->withSession($session)
            ->put(route('online.messages.update', $message), [
                'message' => 'Nao pode editar',
            ])
            ->assertSessionHasErrors('message');

        $this->assertDatabaseMissing('tb22_chat_mensagens', [
            'id' => $message->id,
            'message' => 'Nao pode editar',
        ]);
    }

    public function test_sender_can_only_delete_unread_messages(): void
    {
        $unit = $this->makeUnit('Loja Exclusao');
        $sender = $this->makeUser('Remetente', 3, $unit);
        $recipient = $this->makeUser('Destino', 2, $unit);

        $deletableMessage = ChatMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'sender_role' => 3,
            'sender_unit_id' => $unit->tb2_id,
            'message' => 'Pode excluir',
        ]);

        $readMessage = ChatMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'sender_role' => 3,
            'sender_unit_id' => $unit->tb2_id,
            'message' => 'Nao pode excluir',
            'read_at' => now(),
        ]);

        $session = $this->activeSessionPayload($unit, 3);

        $this->actingAs($sender)
            ->withSession($session)
            ->delete(route('online.messages.destroy', $deletableMessage))
            ->assertOk();

        $this->assertDatabaseMissing('tb22_chat_mensagens', [
            'id' => $deletableMessage->id,
        ]);

        $this->actingAs($sender)
            ->withSession($session)
            ->delete(route('online.messages.destroy', $readMessage))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseHas('tb22_chat_mensagens', [
            'id' => $readMessage->id,
            'message' => 'Nao pode excluir',
        ]);
    }

    private function makeMatrix(string $name): Matriz
    {
        return Matriz::create([
            'nome' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->numerify('###')),
            'status' => 1,
        ]);
    }

    private function makeUnit(string $name, ?Matriz $matrix = null, string $type = 'filial'): Unidade
    {
        return Unidade::create([
            'tb2_nome' => $name,
            'matriz_id' => $matrix?->id,
            'tb2_tipo' => $type,
            'tb2_endereco' => 'Endereco ' . $name,
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => fake()->unique()->numerify('##.###.###/####-##'),
            'tb2_localizacao' => 'https://maps.example.com/' . fake()->slug(),
        ]);
    }

    private function makeUser(string $name, int $role, Unidade $primaryUnit, array $allowedUnits = []): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'funcao' => $role,
            'funcao_original' => $role,
            'tb2_id' => $primaryUnit->tb2_id,
            'matriz_id' => $primaryUnit->matriz_id,
        ]);

        $unitIds = collect($allowedUnits)
            ->prepend($primaryUnit)
            ->map(fn ($unit) => $unit instanceof Unidade ? $unit->tb2_id : (int) $unit)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $user->units()->sync($unitIds);

        return $user;
    }

    private function makePresence(User $user, int $role, Unidade $unit): OnlineUser
    {
        return OnlineUser::create([
            'user_id' => $user->id,
            'session_id' => 'sessao-' . $user->id . '-' . fake()->unique()->numerify('###'),
            'active_role' => $role,
            'active_unit_id' => $unit->tb2_id,
            'last_seen_at' => now(),
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

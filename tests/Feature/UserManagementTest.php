<?php

namespace Tests\Feature;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_user_updates_funcao_original_with_new_role(): void
    {
        $unit = $this->makeUnit('Loja Gestao');

        $manager = User::factory()->create([
            'name' => 'Master Gestao',
            'email' => 'master.gestao@example.com',
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
        ]);
        $manager->units()->sync([$unit->tb2_id]);

        $targetUser = User::factory()->create([
            'name' => 'Usuario Teste',
            'email' => 'usuario.teste@example.com',
            'funcao' => 5,
            'funcao_original' => 5,
            'tb2_id' => $unit->tb2_id,
            'hr_ini' => '08:00',
            'hr_fim' => '17:00',
            'salario' => 1518.00,
            'vr_cred' => 350.00,
        ]);
        $targetUser->units()->sync([$unit->tb2_id]);

        $response = $this
            ->actingAs($manager)
            ->put(route('users.update', ['user' => $targetUser->id]), [
                'name' => 'Usuario Ajustado',
                'email' => 'usuario.ajustado@example.com',
                'funcao' => 3,
                'hr_ini' => '09:00',
                'hr_fim' => '18:00',
                'salario' => 2100.50,
                'vr_cred' => 420.75,
                'tb2_id' => [$unit->tb2_id],
            ]);

        $response->assertRedirect(route('users.show', ['user' => $targetUser->id]));

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Usuario Ajustado',
            'email' => 'usuario.ajustado@example.com',
            'funcao' => 3,
            'funcao_original' => 3,
            'tb2_id' => $unit->tb2_id,
        ]);
    }

    public function test_switch_role_updates_funcao_in_database_without_changing_funcao_original(): void
    {
        $matrix = $this->makeMatrix('Matriz Troca');
        $matrixUnit = $this->makeUnit('Loja Matriz', $matrix, 'matriz');
        $branchUnit = $this->makeUnit('Loja Filial', $matrix, 'filial');

        $user = User::factory()->create([
            'name' => 'Master Troca',
            'email' => 'master.troca@example.com',
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $matrixUnit->tb2_id,
            'matriz_id' => $matrix->id,
        ]);
        $user->units()->sync([$matrixUnit->tb2_id, $branchUnit->tb2_id]);

        $response = $this
            ->actingAs($user)
            ->post(route('reports.switch-unit.update'), [
                'unit_id' => $branchUnit->tb2_id,
                'role' => 3,
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'funcao' => 3,
            'funcao_original' => 0,
            'tb2_id' => $matrixUnit->tb2_id,
        ]);

        $response->assertSessionHas('active_role', 3);
        $response->assertSessionHas('active_unit.id', $branchUnit->tb2_id);
    }

    public function test_switch_screen_lists_only_roles_up_to_original_and_units_from_same_matrix(): void
    {
        $matrixA = $this->makeMatrix('Matriz A');
        $matrixB = $this->makeMatrix('Matriz B');
        $unitA1 = $this->makeUnit('Loja A1', $matrixA, 'matriz');
        $unitA2 = $this->makeUnit('Loja A2', $matrixA, 'filial');
        $unitB1 = $this->makeUnit('Loja B1', $matrixB, 'matriz');

        $user = User::factory()->create([
            'name' => 'Cliente Troca',
            'email' => 'cliente.troca@example.com',
            'funcao' => 6,
            'funcao_original' => 6,
            'tb2_id' => $unitA1->tb2_id,
            'matriz_id' => $matrixA->id,
        ]);
        $user->units()->sync([$unitA1->tb2_id, $unitA2->tb2_id, $unitB1->tb2_id]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'active_unit' => [
                    'id' => $unitA1->tb2_id,
                    'name' => $unitA1->tb2_nome,
                    'address' => $unitA1->tb2_endereco,
                    'cnpj' => $unitA1->tb2_cnpj,
                ],
                'active_role' => 6,
            ])
            ->get(route('reports.switch-unit'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/SwitchUnit')
            ->where('roles', [
                ['value' => 0, 'label' => 'MASTER', 'active' => false],
                ['value' => 1, 'label' => 'GERENTE', 'active' => false],
                ['value' => 2, 'label' => 'SUB-GERENTE', 'active' => false],
                ['value' => 3, 'label' => 'CAIXA', 'active' => false],
                ['value' => 4, 'label' => 'LANCHONETE', 'active' => false],
                ['value' => 5, 'label' => 'FUNCIONARIO', 'active' => false],
                ['value' => 6, 'label' => 'CLIENTE', 'active' => true],
            ])
            ->where('units', [
                ['id' => $unitA2->tb2_id, 'name' => 'Loja A2', 'type' => 'filial', 'active' => false],
                ['id' => $unitA1->tb2_id, 'name' => 'Matriz A', 'type' => 'matriz', 'active' => true],
            ])
            ->missing('roles.7')
        );
    }

    public function test_switch_update_rejects_role_above_original_and_unit_from_other_matrix(): void
    {
        $matrixA = $this->makeMatrix('Matriz Segura A');
        $matrixB = $this->makeMatrix('Matriz Segura B');
        $unitA = $this->makeUnit('Loja Segura A', $matrixA, 'matriz');
        $unitB = $this->makeUnit('Loja Segura B', $matrixB, 'matriz');

        $user = User::factory()->create([
            'name' => 'Gerente Seguro',
            'email' => 'gerente.seguro@example.com',
            'funcao' => 1,
            'funcao_original' => 1,
            'tb2_id' => $unitA->tb2_id,
            'matriz_id' => $matrixA->id,
        ]);
        $user->units()->sync([$unitA->tb2_id, $unitB->tb2_id]);

        $response = $this
            ->actingAs($user)
            ->post(route('reports.switch-unit.update'), [
                'unit_id' => $unitB->tb2_id,
                'role' => 7,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'funcao' => 1,
            'funcao_original' => 1,
            'matriz_id' => $matrixA->id,
        ]);
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
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
        ]);
    }
}

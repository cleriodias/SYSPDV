<?php

namespace Tests\Feature;

use App\Models\Aplicacao;
use App\Models\Matriz;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_user_with_numeric_password(): void
    {
        $matrix = $this->makeMatrix('Matriz Cadastro');
        $unit = $this->makeUnit('Loja Cadastro', $matrix, 'matriz');

        $manager = User::factory()->create([
            'name' => 'Master Cadastro',
            'email' => 'master.cadastro@example.com',
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
        ]);
        $manager->units()->sync([$unit->tb2_id]);

        $response = $this
            ->actingAs($manager)
            ->post(route('users.store'), [
                'name' => 'Joao Silva',
                'email' => 'joao.silva@example.com',
                'password' => '1234',
                'password_confirmation' => '1234',
                'funcao' => 5,
                'hr_ini' => '08:00',
                'hr_fim' => '17:00',
                'salario' => 1518.00,
                'vr_cred' => 350.00,
                'tb2_id' => $unit->tb2_id,
            ]);

        $createdUser = User::query()->where('email', 'joao.silva@example.com')->first();

        $this->assertNotNull($createdUser);
        $response->assertRedirect(route('users.show', ['user' => $createdUser->id]));
        $this->assertTrue(Hash::check('1234', $createdUser->password));
        $this->assertSame($unit->tb2_id, (int) $createdUser->tb2_id);
        $this->assertSame($matrix->id, (int) $createdUser->matriz_id);
    }

    public function test_manager_can_not_create_user_with_non_numeric_password(): void
    {
        $matrix = $this->makeMatrix('Matriz Cadastro Regra');
        $unit = $this->makeUnit('Loja Cadastro Regra', $matrix, 'matriz');

        $manager = User::factory()->create([
            'name' => 'Master Regra',
            'email' => 'master.regra@example.com',
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
        ]);
        $manager->units()->sync([$unit->tb2_id]);

        $response = $this
            ->actingAs($manager)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Joao Silva',
                'email' => 'joao.regra@example.com',
                'password' => '12ab',
                'password_confirmation' => '12ab',
                'funcao' => 5,
                'hr_ini' => '08:00',
                'hr_fim' => '17:00',
                'salario' => 1518.00,
                'vr_cred' => 350.00,
                'tb2_id' => $unit->tb2_id,
            ]);

        $response
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', [
            'email' => 'joao.regra@example.com',
        ]);
    }

    public function test_edit_user_updates_funcao_original_with_new_role(): void
    {
        $matrix = $this->makeMatrix('Matriz Gestao');
        $unit = $this->makeUnit('Loja Gestao', $matrix, 'matriz');

        $manager = User::factory()->create([
            'name' => 'Master Gestao',
            'email' => 'master.gestao@example.com',
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
        ]);
        $manager->units()->sync([$unit->tb2_id]);

        $targetUser = User::factory()->create([
            'name' => 'Usuario Teste',
            'email' => 'usuario.teste@example.com',
            'funcao' => 5,
            'funcao_original' => 5,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
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
                'tb2_id' => $unit->tb2_id,
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

    public function test_switch_role_keeps_funcao_persisted_and_updates_only_the_session(): void
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
            'funcao' => 0,
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
            'name' => 'Caixa Troca',
            'email' => 'caixa.troca@example.com',
            'funcao' => 3,
            'funcao_original' => 3,
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
            ->where('initialRole', null)
            ->where('currentUnitId', $unitA1->tb2_id)
            ->where('currentMatrixUnitId', $unitA1->tb2_id)
            ->where('initialSelectedUnitId', $unitA1->tb2_id)
            ->where('roles', [
                ['value' => 3, 'label' => 'CAIXA', 'bossOnly' => false, 'active' => false],
                ['value' => 4, 'label' => 'LANCHONETE', 'bossOnly' => false, 'active' => false],
                ['value' => 5, 'label' => 'FUNCIONARIO', 'bossOnly' => false, 'active' => false],
                ['value' => 6, 'label' => 'CLIENTE', 'bossOnly' => false, 'active' => true],
            ])
            ->where('currentSessionUnitLabel', 'Loja A1')
            ->where('units', [
                ['id' => $unitA1->tb2_id, 'name' => 'Loja A1', 'type' => 'matriz', 'matrixId' => $matrixA->id, 'matrixName' => 'Matriz A', 'status' => 1, 'loginEnabled' => true, 'selectable' => true, 'bossOnly' => false, 'active' => true],
            ])
            ->has('unitGroups', 1)
            ->where('unitGroups.0.matrix.name', 'Matriz A')
            ->where('unitGroups.0.matrixUnit.id', $unitA1->tb2_id)
            ->where('unitGroups.0.matrixUnit.name', 'Loja A1')
            ->where('unitGroups.0.matrixUnit.type', 'matriz')
            ->where('unitGroups.0.matrixUnit.status', 1)
            ->where('unitGroups.0.matrixUnit.loginEnabled', true)
            ->where('unitGroups.0.matrixUnit.selectable', true)
            ->where('unitGroups.0.matrixUnit.active', true)
            ->where('unitGroups.0.branches', [
                ['id' => $unitA2->tb2_id, 'name' => 'Loja A2', 'type' => 'filial', 'matrixId' => $matrixA->id, 'matrixName' => 'Matriz A', 'status' => 1, 'loginEnabled' => true, 'selectable' => true, 'bossOnly' => false, 'active' => false, 'matrixUnitId' => $unitA1->tb2_id],
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

    public function test_boss_sees_units_from_all_matrices_on_switch_screen(): void
    {
        $matrixA = $this->makeMatrix('Matriz Boss A');
        $matrixB = $this->makeMatrix('Matriz Boss B');
        $unitA1 = $this->makeUnit('Loja Boss A1', $matrixA, 'matriz');
        $unitA2 = $this->makeUnit('Loja Boss A2', $matrixA, 'filial');
        $unitB1 = $this->makeUnit('Loja Boss B1', $matrixB, 'matriz');
        $unitB2 = $this->makeUnit('Loja Boss B2', $matrixB, 'filial');

        $boss = User::factory()->create([
            'id' => 1,
            'name' => 'Boss Global',
            'email' => 'boss.global@example.com',
            'funcao' => 7,
            'funcao_original' => 7,
            'tb2_id' => $unitA1->tb2_id,
            'matriz_id' => $matrixA->id,
        ]);
        $boss->units()->sync([$unitA1->tb2_id]);

        $response = $this
            ->actingAs($boss)
            ->withSession([
                'active_unit' => [
                    'id' => $unitA1->tb2_id,
                    'name' => $unitA1->tb2_nome,
                    'address' => $unitA1->tb2_endereco,
                    'cnpj' => $unitA1->tb2_cnpj,
                ],
                'active_role' => 7,
            ])
            ->get(route('reports.switch-unit'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/SwitchUnit')
            ->where('initialRole', null)
            ->where('currentUnitId', $unitA1->tb2_id)
            ->where('currentMatrixUnitId', $unitA1->tb2_id)
            ->where('initialSelectedUnitId', $unitA1->tb2_id)
            ->where('currentSessionUnitLabel', 'Loja Boss A1')
            ->where('units', [
                ['id' => $unitA1->tb2_id, 'name' => 'Loja Boss A1', 'type' => 'matriz', 'matrixId' => $matrixA->id, 'matrixName' => 'Matriz Boss A', 'status' => 1, 'loginEnabled' => true, 'selectable' => true, 'bossOnly' => false, 'active' => true],
                ['id' => $unitB1->tb2_id, 'name' => 'Loja Boss B1', 'type' => 'matriz', 'matrixId' => $matrixB->id, 'matrixName' => 'Matriz Boss B', 'status' => 1, 'loginEnabled' => true, 'selectable' => true, 'bossOnly' => false, 'active' => false],
            ])
            ->has('unitGroups', 2)
            ->where('unitGroups.0.matrix.name', 'Matriz Boss A')
            ->where('unitGroups.0.matrixUnit.id', $unitA1->tb2_id)
            ->where('unitGroups.0.matrixUnit.status', 1)
            ->where('unitGroups.0.matrixUnit.loginEnabled', true)
            ->where('unitGroups.0.matrixUnit.selectable', true)
            ->where('unitGroups.0.matrixUnit.active', true)
            ->where('unitGroups.0.branches', [
                ['id' => $unitA2->tb2_id, 'name' => 'Loja Boss A2', 'type' => 'filial', 'matrixId' => $matrixA->id, 'matrixName' => 'Matriz Boss A', 'status' => 1, 'loginEnabled' => true, 'selectable' => true, 'bossOnly' => false, 'active' => false, 'matrixUnitId' => $unitA1->tb2_id],
            ])
            ->where('unitGroups.1.matrix.name', 'Matriz Boss B')
            ->where('unitGroups.1.matrixUnit.id', $unitB1->tb2_id)
            ->where('unitGroups.1.matrixUnit.status', 1)
            ->where('unitGroups.1.matrixUnit.loginEnabled', true)
            ->where('unitGroups.1.matrixUnit.selectable', true)
            ->where('unitGroups.1.matrixUnit.active', false)
            ->where('unitGroups.1.branches', [
                ['id' => $unitB2->tb2_id, 'name' => 'Loja Boss B2', 'type' => 'filial', 'matrixId' => $matrixB->id, 'matrixName' => 'Matriz Boss B', 'status' => 1, 'loginEnabled' => true, 'selectable' => true, 'bossOnly' => false, 'active' => false, 'matrixUnitId' => $unitB1->tb2_id],
            ])
            ->where('roles', [
                ['value' => 7, 'label' => 'BOSS', 'bossOnly' => true, 'active' => false],
                ['value' => 0, 'label' => 'MASTER', 'bossOnly' => false, 'active' => true],
                ['value' => 1, 'label' => 'GERENTE', 'bossOnly' => false, 'active' => false],
                ['value' => 2, 'label' => 'SUB-GERENTE', 'bossOnly' => false, 'active' => false],
                ['value' => 3, 'label' => 'CAIXA', 'bossOnly' => false, 'active' => false],
                ['value' => 4, 'label' => 'LANCHONETE', 'bossOnly' => false, 'active' => false],
                ['value' => 5, 'label' => 'FUNCIONARIO', 'bossOnly' => false, 'active' => false],
                ['value' => 6, 'label' => 'CLIENTE', 'bossOnly' => false, 'active' => false],
            ])
        );
    }

    public function test_boss_sees_inactive_units_but_can_not_select_them(): void
    {
        $matrixA = $this->makeMatrix('Matriz Boss Ativa');
        $matrixB = $this->makeMatrix('Matriz Boss Inativa');
        $activeUnit = $this->makeUnit('Loja Boss Ativa', $matrixA, 'matriz');
        $inactiveUnit = $this->makeUnit('Loja Boss Inativa', $matrixB, 'matriz', 0);

        $boss = User::factory()->create([
            'id' => 1,
            'name' => 'Boss Inativa',
            'email' => 'boss.inativa@example.com',
            'funcao' => 7,
            'funcao_original' => 7,
            'tb2_id' => $activeUnit->tb2_id,
            'matriz_id' => $matrixA->id,
        ]);
        $boss->units()->sync([$activeUnit->tb2_id]);

        $response = $this
            ->actingAs($boss)
            ->get(route('reports.switch-unit'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/SwitchUnit')
            ->where('initialRole', null)
            ->where('currentUnitId', $activeUnit->tb2_id)
            ->where('currentMatrixUnitId', $activeUnit->tb2_id)
            ->where('initialSelectedUnitId', $activeUnit->tb2_id)
            ->where('units', [
                ['id' => $activeUnit->tb2_id, 'name' => 'Loja Boss Ativa', 'type' => 'matriz', 'matrixId' => $matrixA->id, 'matrixName' => 'Matriz Boss Ativa', 'status' => 1, 'loginEnabled' => true, 'selectable' => true, 'bossOnly' => false, 'active' => true],
                ['id' => $inactiveUnit->tb2_id, 'name' => 'Loja Boss Inativa', 'type' => 'matriz', 'matrixId' => $matrixB->id, 'matrixName' => 'Matriz Boss Inativa', 'status' => 0, 'loginEnabled' => true, 'selectable' => false, 'bossOnly' => false, 'active' => false],
            ])
            ->where('unitGroups.0.branches', [])
            ->where('unitGroups.1.branches', [])
        );

        $updateResponse = $this
            ->actingAs($boss)
            ->post(route('reports.switch-unit.update'), [
                'unit_id' => $inactiveUnit->tb2_id,
                'role' => 7,
            ]);

        $updateResponse->assertForbidden();
    }

    private function makeMatrix(string $name): Matriz
    {
        return Matriz::create([
            'nome' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->numerify('###')),
            'tb28_id' => Aplicacao::PADARIA_NFE,
            'status' => 1,
            'pagamento_ativo' => true,
        ]);
    }

    private function makeUnit(
        string $name,
        ?Matriz $matrix = null,
        string $type = 'filial',
        int $status = 1,
        bool $loginEnabled = true,
    ): Unidade
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
            'tb2_status' => $status,
            'pagamento_ativo' => true,
            'login_liberado' => $loginEnabled,
        ]);
    }
}

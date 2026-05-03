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

class MatrixManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_creates_missing_matrix_unit_automatically(): void
    {
        $boss = $this->makeBossUser();
        $matriz = $this->makeMatrix('Empresa sem unidade matriz');

        $response = $this
            ->actingAs($boss)
            ->withSession([
                'active_role' => 7,
                'active_unit' => [
                    'id' => $boss->tb2_id,
                    'name' => 'DASH',
                    'address' => 'Endereco DASH',
                    'cnpj' => '00000000000000',
                ],
            ])
            ->get(route('matrizes.edit', $matriz));

        $response->assertOk();

        $this->assertDatabaseHas('tb2_unidades', [
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'matriz',
            'tb2_nome' => 'Empresa sem unidade matriz',
        ]);
    }

    public function test_index_lists_the_application_name_for_each_matrix(): void
    {
        $boss = $this->makeBossUser();
        $matriz = Matriz::create([
            'nome' => 'Empresa NFe',
            'slug' => 'empresa-nfe',
            'cnpj' => '12345678000199',
            'tb28_id' => Aplicacao::NFE,
            'status' => 1,
            'pagamento_ativo' => true,
        ]);

        $response = $this
            ->actingAs($boss)
            ->withSession($this->withBossSession($boss))
            ->get(route('matrizes.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Matrizes/Index')
            ->where('matrizes', fn ($matrizes) => collect($matrizes)->contains(
                fn ($item) => ($item['nome'] ?? null) === 'Empresa NFe'
                    && data_get($item, 'aplicacao.tb28_id') === Aplicacao::NFE
                    && data_get($item, 'aplicacao.tb28_nome') === 'NFe Corretora de Seguros'
            ))
        );
    }

    public function test_create_lists_nfe_application_with_expanded_name(): void
    {
        $boss = $this->makeBossUser();

        Aplicacao::query()->upsert([
            [
                'tb28_id' => Aplicacao::NFE,
                'tb28_nome' => 'NFe',
                'tb28_slug' => 'nfe',
                'tb28_rota_inicial' => 'nfe?unit_id={unit_id}',
            ],
        ], ['tb28_id'], ['tb28_nome', 'tb28_slug', 'tb28_rota_inicial']);

        $response = $this
            ->actingAs($boss)
            ->withSession($this->withBossSession($boss))
            ->get(route('matrizes.create'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Matrizes/Create')
            ->where('applications', fn ($applications) => collect($applications)->contains(
                fn ($item) => ($item['tb28_id'] ?? null) === Aplicacao::NFE
                    && ($item['tb28_nome'] ?? null) === 'NFe Corretora de Seguros'
            ))
        );
    }

    public function test_update_repairs_missing_matrix_unit_instead_of_returning_404(): void
    {
        $boss = $this->makeBossUser();
        $matriz = $this->makeMatrix('Empresa quebrada');
        $fallbackUnit = Unidade::create([
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'filial',
            'tb2_nome' => 'Filial antiga',
            'tb2_endereco' => 'Endereco Filial antiga',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000198',
            'tb2_localizacao' => 'https://maps.google.com/?q=Filial+antiga',
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
        ]);
        $this->makeMatrixMaster($matriz, $fallbackUnit, 'Master Ajustado', 'master.ajustado@example.com', '1234');

        $response = $this
            ->actingAs($boss)
            ->withSession([
                'active_role' => 7,
                'active_unit' => [
                    'id' => $boss->tb2_id,
                    'name' => 'DASH',
                    'address' => 'Endereco DASH',
                    'cnpj' => '00000000000000',
                ],
            ])
            ->put(route('matrizes.update', $matriz), [
                'nome' => 'Empresa ajustada',
            'cnpj' => '12345678000199',
            'tb28_id' => Aplicacao::PADARIA,
            'master_name' => 'Master Ajustado',
            'master_email' => 'master.ajustado@example.com',
            'master_password' => '',
                'master_password_confirmation' => '',
                'unit_name' => 'Unidade matriz ajustada',
                'unit_address' => 'Rua Central, 100',
                'unit_cep' => '72900-000',
                'unit_phone' => '(61) 99999-9999',
                'unit_cnpj' => '12345678000199',
                'unit_location' => 'https://maps.google.com/?q=Empresa+Ajustada',
                'status' => true,
                'pagamento_ativo' => true,
                'plano_mensal_valor' => 320.50,
                'plano_contratado_em' => '2026-04-25',
            ]);

        $response
            ->assertRedirect(route('matrizes.index'))
            ->assertSessionHas('success', 'Dados da matriz atualizados com sucesso.');

        $this->assertDatabaseHas('matrizes', [
            'id' => $matriz->id,
            'nome' => 'Empresa ajustada',
            'slug' => 'empresa-ajustada',
            'cnpj' => '12345678000199',
            'tb28_id' => Aplicacao::PADARIA,
            'status' => 1,
        ]);

        $this->assertDatabaseHas('tb2_unidades', [
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'matriz',
            'tb2_nome' => 'Unidade matriz ajustada',
            'tb2_endereco' => 'Rua Central, 100',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_status' => 1,
        ]);
    }

    public function test_update_also_updates_the_matrix_master_without_changing_password_when_blank(): void
    {
        $boss = $this->makeBossUser();
        $matriz = $this->makeMatrix('Empresa com master');
        $matrixUnit = $this->makeMatrixUnit($matriz, 'Unidade Empresa com master');
        $master = $this->makeMatrixMaster($matriz, $matrixUnit, 'Master Antigo', 'master.antigo@example.com', '4321');

        $response = $this
            ->actingAs($boss)
            ->withSession($this->withBossSession($boss))
            ->put(route('matrizes.update', $matriz), [
                'nome' => 'Empresa com master',
                'cnpj' => '12345678000199',
                'tb28_id' => Aplicacao::PADARIA_NFE,
                'master_name' => 'Master Novo',
                'master_email' => 'master.novo@example.com',
                'master_password' => '',
                'master_password_confirmation' => '',
                'unit_name' => 'Unidade Empresa com master',
                'unit_address' => 'Rua Central, 100',
                'unit_cep' => '72900-000',
                'unit_phone' => '(61) 99999-9999',
                'unit_cnpj' => '12345678000199',
                'unit_location' => 'https://maps.google.com/?q=Empresa+Master',
                'status' => true,
                'pagamento_ativo' => true,
                'plano_mensal_valor' => 320.50,
                'plano_contratado_em' => '2026-04-25',
            ]);

        $response->assertRedirect(route('matrizes.index'));

        $master->refresh();

        $this->assertSame('Master Novo', $master->name);
        $this->assertSame('master.novo@example.com', $master->email);
        $this->assertTrue(Hash::check('4321', $master->password));
    }

    public function test_update_changes_the_matrix_master_password_when_informed(): void
    {
        $boss = $this->makeBossUser();
        $matriz = $this->makeMatrix('Empresa troca senha');
        $matrixUnit = $this->makeMatrixUnit($matriz, 'Unidade Empresa troca senha');
        $master = $this->makeMatrixMaster($matriz, $matrixUnit, 'Master Senha', 'master.senha@example.com', '1234');

        $response = $this
            ->actingAs($boss)
            ->withSession($this->withBossSession($boss))
            ->put(route('matrizes.update', $matriz), [
                'nome' => 'Empresa troca senha',
                'cnpj' => '12345678000199',
                'tb28_id' => Aplicacao::PADARIA_NFE,
                'master_name' => 'Master Senha',
                'master_email' => 'master.senha@example.com',
                'master_password' => '9876',
                'master_password_confirmation' => '9876',
                'unit_name' => 'Unidade Empresa troca senha',
                'unit_address' => 'Rua Central, 100',
                'unit_cep' => '72900-000',
                'unit_phone' => '(61) 99999-9999',
                'unit_cnpj' => '12345678000199',
                'unit_location' => 'https://maps.google.com/?q=Empresa+Senha',
                'status' => true,
                'pagamento_ativo' => true,
                'plano_mensal_valor' => 320.50,
                'plano_contratado_em' => '2026-04-25',
            ]);

        $response->assertRedirect(route('matrizes.index'));

        $master->refresh();

        $this->assertTrue(Hash::check('9876', $master->password));
    }

    public function test_update_recreates_missing_matrix_master_using_the_edited_data(): void
    {
        $boss = $this->makeBossUser();
        $matriz = $this->makeMatrix('Empresa sem master');
        $matrixUnit = $this->makeMatrixUnit($matriz, 'Unidade Empresa sem master');

        $response = $this
            ->actingAs($boss)
            ->withSession($this->withBossSession($boss))
            ->put(route('matrizes.update', $matriz), [
                'nome' => 'Empresa sem master',
                'cnpj' => '12345678000199',
                'tb28_id' => Aplicacao::PADARIA,
                'master_name' => 'Master Criado',
                'master_email' => 'master.criado@example.com',
                'master_password' => '2468',
                'master_password_confirmation' => '2468',
                'unit_name' => 'Unidade Empresa sem master',
                'unit_address' => 'Rua Central, 100',
                'unit_cep' => '72900-000',
                'unit_phone' => '(61) 99999-9999',
                'unit_cnpj' => '12345678000199',
                'unit_location' => 'https://maps.google.com/?q=Empresa+Sem+Master',
                'status' => true,
                'pagamento_ativo' => true,
                'plano_mensal_valor' => 320.50,
                'plano_contratado_em' => '2026-04-25',
            ]);

        $response->assertRedirect(route('matrizes.index'));

        $createdMaster = User::query()
            ->where('matriz_id', $matriz->id)
            ->where('funcao_original', 0)
            ->first();

        $this->assertNotNull($createdMaster);
        $this->assertSame('Master Criado', $createdMaster->name);
        $this->assertSame('master.criado@example.com', $createdMaster->email);
        $this->assertSame($matrixUnit->tb2_id, $createdMaster->tb2_id);
        $this->assertTrue(Hash::check('2468', $createdMaster->password));
        $this->assertDatabaseHas('tb2_unidade_user', [
            'user_id' => $createdMaster->id,
            'tb2_id' => $matrixUnit->tb2_id,
        ]);
    }

    public function test_missing_matrix_redirects_back_to_index_with_flash_error(): void
    {
        $boss = $this->makeBossUser();

        $response = $this->actingAs($boss)->get('/matrizes/999/edit');

        $response
            ->assertRedirect(route('matrizes.index'))
            ->assertSessionHas('error', 'Matriz nao encontrada.');
    }

    private function makeBossUser(): User
    {
        $unit = Unidade::create([
            'tb2_nome' => 'DASH',
            'matriz_id' => null,
            'tb2_tipo' => 'filial',
            'tb2_endereco' => 'Endereco DASH',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 90000-0000',
            'tb2_cnpj' => '00000000000000',
            'tb2_localizacao' => 'https://maps.google.com/?q=DASH',
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
        ]);

        $user = User::factory()->create([
            'id' => 1,
            'name' => 'Boss Teste',
            'email' => 'boss@example.com',
            'funcao' => 7,
            'funcao_original' => 7,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => null,
            'cod_acesso' => 'BOSS7',
        ]);

        $user->units()->sync([$unit->tb2_id]);

        return $user;
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

    private function makeMatrixUnit(Matriz $matriz, string $name): Unidade
    {
        return Unidade::create([
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'matriz',
            'tb2_nome' => $name,
            'tb2_endereco' => 'Endereco ' . $name,
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_localizacao' => 'https://maps.google.com/?q=' . rawurlencode($name),
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
        ]);
    }

    private function makeMatrixMaster(Matriz $matriz, Unidade $matrixUnit, string $name, string $email, string $password): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'funcao' => 0,
            'funcao_original' => 0,
            'hr_ini' => '00:00',
            'hr_fim' => '23:00',
            'salario' => 0,
            'vr_cred' => 0,
            'tb2_id' => $matrixUnit->tb2_id,
            'matriz_id' => $matriz->id,
            'cod_acesso' => '1234',
        ]);

        $user->units()->sync([$matrixUnit->tb2_id]);

        return $user;
    }

    private function withBossSession(User $boss): array
    {
        return [
            'active_role' => 7,
            'active_unit' => [
                'id' => $boss->tb2_id,
                'name' => 'DASH',
                'address' => 'Endereco DASH',
                'cnpj' => '00000000000000',
            ],
        ];
    }
}

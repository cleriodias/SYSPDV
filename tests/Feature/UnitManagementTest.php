<?php

namespace Tests\Feature;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_delegated_boss_as_master_can_create_branch_when_a_valid_matrix_is_selected(): void
    {
        $matrix = $this->makeMatrix('Matriz Operacional');
        $dashUnit = $this->makeDashUnit();
        $matrixUnit = $this->makeUnit('Unidade Matriz Operacional', $matrix, 'matriz');
        $boss = $this->makeBossUser($dashUnit);

        $response = $this
            ->actingAs($boss)
            ->from(route('units.create'))
            ->withSession([
                'active_role' => 0,
                'active_unit' => [
                    'id' => $matrixUnit->tb2_id,
                    'name' => $matrixUnit->tb2_nome,
                    'address' => $matrixUnit->tb2_endereco,
                    'cnpj' => $matrixUnit->tb2_cnpj,
                ],
            ])
            ->post(route('units.store'), [
                'tb2_nome' => 'Filial Operacional',
                'tb2_endereco' => 'Rua da Filial, 100',
                'tb2_cep' => '72000-000',
                'tb2_fone' => '(61) 98888-0000',
                'tb2_cnpj' => '12345678000199',
                'tb2_localizacao' => 'https://maps.google.com/?q=Filial+Operacional',
                'tb2_status' => 1,
            ]);

        $createdUnit = Unidade::query()->where('tb2_nome', 'Filial Operacional')->first();

        $response
            ->assertRedirect(route('units.show', ['unit' => $createdUnit?->tb2_id]))
            ->assertSessionHas('success', 'Unidade cadastrada com sucesso!');

        $this->assertNotNull($createdUnit);
        $this->assertDatabaseHas('tb2_unidades', [
            'tb2_id' => $createdUnit->tb2_id,
            'matriz_id' => $matrix->id,
            'tb2_tipo' => 'filial',
            'tb2_nome' => 'Filial Operacional',
            'tb2_status' => 1,
        ]);
    }

    public function test_delegated_boss_as_master_can_not_create_branch_while_active_context_is_dash(): void
    {
        $dashUnit = $this->makeDashUnit();
        $boss = $this->makeBossUser($dashUnit);

        $response = $this
            ->actingAs($boss)
            ->from(route('units.create'))
            ->withSession([
                'active_role' => 0,
                'active_unit' => [
                    'id' => $dashUnit->tb2_id,
                    'name' => $dashUnit->tb2_nome,
                    'address' => $dashUnit->tb2_endereco,
                    'cnpj' => $dashUnit->tb2_cnpj,
                ],
            ])
            ->post(route('units.store'), [
                'tb2_nome' => 'Filial DASH',
                'tb2_endereco' => 'Rua sem matriz',
                'tb2_cep' => '72000-000',
                'tb2_fone' => '(61) 97777-0000',
                'tb2_cnpj' => '00999999000199',
                'tb2_localizacao' => 'https://maps.google.com/?q=Filial+DASH',
                'tb2_status' => 1,
            ]);

        $response
            ->assertRedirect(route('units.create'))
            ->assertSessionHas('error', 'Selecione primeiro uma matriz valida na troca de perfil antes de cadastrar uma filial.');

        $this->assertDatabaseMissing('tb2_unidades', [
            'tb2_nome' => 'Filial DASH',
        ]);
    }

    private function makeBossUser(Unidade $dashUnit): User
    {
        $user = User::factory()->create([
            'name' => 'Boss Delegado',
            'email' => 'boss.delegado@example.com',
            'funcao' => 7,
            'funcao_original' => 7,
            'tb2_id' => $dashUnit->tb2_id,
            'matriz_id' => null,
            'cod_acesso' => '7000',
        ]);

        $user->units()->sync([$dashUnit->tb2_id]);

        return $user;
    }

    private function makeDashUnit(): Unidade
    {
        return Unidade::create([
            'tb2_nome' => 'DASH',
            'matriz_id' => null,
            'tb2_tipo' => 'filial',
            'tb2_endereco' => 'Endereco DASH',
            'tb2_cep' => '72000-000',
            'tb2_fone' => '(61) 90000-0000',
            'tb2_cnpj' => '00000000000000',
            'tb2_localizacao' => 'https://maps.google.com/?q=DASH',
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
        ]);
    }

    private function makeUnit(string $name, Matriz $matrix, string $type = 'filial'): Unidade
    {
        return Unidade::create([
            'tb2_nome' => $name,
            'matriz_id' => $matrix->id,
            'tb2_tipo' => $type,
            'tb2_endereco' => 'Endereco ' . $name,
            'tb2_cep' => '72000-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => fake()->unique()->numerify('##.###.###/####-##'),
            'tb2_localizacao' => 'https://maps.google.com/?q=' . rawurlencode($name),
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
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
}

<?php

namespace Tests\Feature;

use App\Models\Matriz;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatrixManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_creates_missing_matrix_unit_automatically(): void
    {
        $boss = $this->makeBossUser();
        $matriz = $this->makeMatrix('Empresa sem unidade matriz');

        $response = $this->actingAs($boss)->get(route('matrizes.edit', $matriz));

        $response->assertOk();

        $this->assertDatabaseHas('tb2_unidades', [
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'matriz',
            'tb2_nome' => 'Empresa sem unidade matriz',
        ]);
    }

    public function test_update_repairs_missing_matrix_unit_instead_of_returning_404(): void
    {
        $boss = $this->makeBossUser();
        $matriz = $this->makeMatrix('Empresa quebrada');

        $response = $this
            ->actingAs($boss)
            ->put(route('matrizes.update', $matriz), [
                'nome' => 'Empresa ajustada',
                'cnpj' => '12345678000199',
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
        $matriz = $this->makeMatrix('Matriz Boss');
        $unit = Unidade::create([
            'tb2_nome' => 'Unidade Boss',
            'matriz_id' => $matriz->id,
            'tb2_tipo' => 'matriz',
            'tb2_endereco' => 'Endereco Boss',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '11111111000191',
            'tb2_localizacao' => 'https://maps.google.com/?q=Unidade+Boss',
            'tb2_status' => 1,
            'pagamento_ativo' => true,
            'login_liberado' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Boss Teste',
            'email' => 'boss@example.com',
            'funcao' => 7,
            'funcao_original' => 7,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matriz->id,
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
            'status' => 1,
            'pagamento_ativo' => true,
        ]);
    }
}

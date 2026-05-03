<?php

namespace Tests\Feature;

use App\Models\Aplicacao;
use App\Models\Matriz;
use App\Models\NfeInsurer;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NfeInsurerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_can_create_matrix_scoped_insurer(): void
    {
        $matrix = $this->makeMatrix('Matriz NFe Seguradora');
        $unit = $this->makeUnit('Unidade NFe Seguradora', $matrix, 'matriz');
        $user = $this->makeManagementUser($matrix, $unit);

        $response = $this
            ->actingAs($user)
            ->post(route('nfe.insurers.store'), [
                'tb31_nome_fantasia' => 'Porto Seguro',
                'tb31_razao_social' => 'Porto Seguro Companhia',
                'tb31_cnpj' => '12345678000199',
                'tb31_codigo_susep' => 'PORTO-001',
                'tb31_status' => '1',
                'tb31_usa_integracao' => '0',
                'tb31_codigo_externo_integracao' => '',
                'tb31_observacoes_operacionais' => 'Integracao futura.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tb31_nfe_seguradoras', [
            'matriz_id' => $matrix->id,
            'tb31_nome_fantasia' => 'Porto Seguro',
            'tb31_status' => 1,
            'tb31_usa_integracao' => 0,
        ]);
    }

    public function test_integration_insurer_requires_complete_registration(): void
    {
        $matrix = $this->makeMatrix('Matriz NFe Integracao');
        $unit = $this->makeUnit('Unidade NFe Integracao', $matrix, 'matriz');
        $user = $this->makeManagementUser($matrix, $unit);

        $response = $this
            ->actingAs($user)
            ->from(route('nfe.insurers.create'))
            ->post(route('nfe.insurers.store'), [
                'tb31_nome_fantasia' => 'Seguradora Integrada',
                'tb31_razao_social' => '',
                'tb31_cnpj' => '',
                'tb31_codigo_susep' => '',
                'tb31_status' => '1',
                'tb31_usa_integracao' => '1',
                'tb31_codigo_externo_integracao' => '',
                'tb31_observacoes_operacionais' => '',
            ]);

        $response
            ->assertRedirect(route('nfe.insurers.create'))
            ->assertSessionHasErrors([
                'tb31_razao_social',
                'tb31_cnpj',
                'tb31_codigo_susep',
                'tb31_codigo_externo_integracao',
            ]);

        $this->assertDatabaseCount('tb31_nfe_seguradoras', 0);
    }

    private function makeMatrix(string $name): Matriz
    {
        return Matriz::create([
            'nome' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->numerify('###')),
            'tb28_id' => Aplicacao::NFE,
            'status' => 1,
            'pagamento_ativo' => true,
        ]);
    }

    private function makeUnit(string $name, Matriz $matrix, string $type): Unidade
    {
        return Unidade::create([
            'tb2_nome' => $name,
            'matriz_id' => $matrix->id,
            'tb2_tipo' => $type,
            'tb2_endereco' => 'Endereco ' . $name,
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_localizacao' => 'https://maps.example.com/' . rawurlencode($name),
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);
    }

    private function makeManagementUser(Matriz $matrix, Unidade $unit): User
    {
        $user = User::factory()->create([
            'name' => 'Master NFe Seguradora',
            'email' => 'master.nfe.seguradora@example.com',
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => $matrix->id,
        ]);

        $user->units()->sync([$unit->tb2_id]);

        return $user;
    }
}

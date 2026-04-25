<?php

namespace Tests\Feature;

use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanchoneteTerminalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_access_accepts_user_with_lanchonete_role_in_funcao_original(): void
    {
        $unit = $this->makeUnit('Loja Lanchonete');
        $authenticatedUser = $this->makeUser('Master Auth', 0, 0, $unit, 'AUTH1');
        $accessUser = $this->makeUser('Operador Lanchonete', 7, 4, $unit, 'LANCH1');

        $response = $this
            ->actingAs($authenticatedUser)
            ->postJson(route('lanchonete.terminal.access'), [
                'cod_acesso' => 'lanch1',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('id', $accessUser->id)
            ->assertJsonPath('name', 'Operador Lanchonete')
            ->assertJsonPath('cod_acesso', 'LANCH1');
    }

    public function test_validate_access_returns_json_validation_error_for_empty_code(): void
    {
        $unit = $this->makeUnit('Loja Lanchonete');
        $authenticatedUser = $this->makeUser('Master Auth', 0, 0, $unit, 'AUTH1');

        $response = $this
            ->actingAs($authenticatedUser)
            ->postJson(route('lanchonete.terminal.access'), [
                'cod_acesso' => '',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Informe o codigo de acesso.')
            ->assertJsonValidationErrors(['cod_acesso']);
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
        ]);
    }

    private function makeUser(
        string $name,
        int $role,
        int $originalRole,
        Unidade $unit,
        string $accessCode,
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'funcao' => $role,
            'funcao_original' => $originalRole,
            'tb2_id' => $unit->tb2_id,
            'cod_acesso' => $accessCode,
        ]);

        $user->units()->sync([$unit->tb2_id]);

        return $user;
    }
}

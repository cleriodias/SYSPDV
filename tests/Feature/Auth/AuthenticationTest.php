<?php

namespace Tests\Feature\Auth;

use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $unit = $this->makeLoginUnit('Loja Teste Login');
        $user = User::factory()->create([
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
        ]);
        $user->units()->sync([$unit->tb2_id]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $unit = $this->makeLoginUnit('Loja Teste Senha');
        $user = User::factory()->create([
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
        ]);
        $user->units()->sync([$unit->tb2_id]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $unit = $this->makeLoginUnit('Loja Teste Logout');
        $user = User::factory()->create([
            'funcao' => 0,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
        ]);
        $user->units()->sync([$unit->tb2_id]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_login_restores_funcao_from_funcao_original_before_building_session(): void
    {
        $unit = $this->makeLoginUnit('Loja Login');
        $user = User::factory()->create([
            'email' => 'master.login@example.com',
            'password' => 'password',
            'funcao' => 6,
            'funcao_original' => 0,
            'tb2_id' => $unit->tb2_id,
        ]);
        $user->units()->sync([$unit->tb2_id]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('active_role', 0);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'funcao' => 0,
            'funcao_original' => 0,
        ]);
    }

    public function test_login_uses_funcao_original_for_boss_even_when_funcao_is_temporarily_different(): void
    {
        $unit = $this->makeLoginUnit('Loja Boss');
        $user = User::factory()->create([
            'email' => 'boss.login@example.com',
            'password' => 'password',
            'funcao' => 3,
            'funcao_original' => 7,
            'tb2_id' => $unit->tb2_id,
            'matriz_id' => null,
        ]);
        $user->units()->sync([$unit->tb2_id]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('active_role', 7);
        $response->assertSessionMissing('active_unit');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'funcao' => 7,
            'funcao_original' => 7,
        ]);
    }

    private function makeLoginUnit(string $name): Unidade
    {
        $digits = substr(preg_replace('/\D+/', '', crc32($name) . '12345678901234'), 0, 14);

        return Unidade::create([
            'tb2_nome' => $name,
            'tb2_endereco' => 'Endereco ' . $name,
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => str_pad($digits, 14, '0'),
            'tb2_localizacao' => 'https://maps.example.com/' . strtolower(str_replace(' ', '-', $name)),
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);
    }
}

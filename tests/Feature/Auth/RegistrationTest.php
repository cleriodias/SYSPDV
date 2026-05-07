<?php

namespace Tests\Feature\Auth;

use App\Models\Unidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->createDefaultUnit();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '1234',
            'password_confirmation' => '1234',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_registration_rejects_non_numeric_password(): void
    {
        $this->createDefaultUnit();

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '12ab',
            'password_confirmation' => '12ab',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    private function createDefaultUnit(): Unidade
    {
        $unit = Unidade::query()->find(1);

        if ($unit) {
            return $unit;
        }

        $unit = new Unidade([
            'tb2_nome' => 'Loja Teste',
            'matriz_id' => null,
            'tb2_tipo' => 'matriz',
            'tb2_endereco' => 'Endereco Loja Teste',
            'tb2_cep' => '72900-000',
            'tb2_fone' => '(61) 99999-9999',
            'tb2_cnpj' => '12345678000199',
            'tb2_localizacao' => 'https://maps.example.com/loja-teste',
            'tb2_status' => 1,
            'login_liberado' => true,
        ]);
        $unit->tb2_id = 1;
        $unit->save();

        return $unit;
    }
}

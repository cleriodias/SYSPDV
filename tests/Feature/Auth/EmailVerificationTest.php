<?php

namespace Tests\Feature\Auth;

use App\Models\Unidade;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $this->createDefaultUnit();
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $this->createDefaultUnit();
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard').'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $this->createDefaultUnit();
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
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

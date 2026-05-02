<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetMailConfigurationTest extends TestCase
{
    public function test_password_reset_informs_when_mailer_is_log(): void
    {
        config()->set('mail.default', 'log');

        $response = $this
            ->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'teste@example.com',
            ]);

        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors([
                'email' => 'O envio de redefinicao de senha nao esta configurado para e-mail real neste ambiente.',
            ]);
    }

    public function test_password_reset_informs_when_smtp_credentials_are_placeholders(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.host', 'smart.iagentesmtp.com.br');
        config()->set('mail.mailers.smtp.username', 'email_do_usuario_na_iagente');
        config()->set('mail.mailers.smtp.password', 'senha_do_usuario_na_iagente');
        config()->set('mail.from.address', 'cleriodias@gmail.com');

        $response = $this
            ->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'teste@example.com',
            ]);

        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors([
                'email' => 'O envio de redefinicao de senha esta sem as credenciais SMTP reais.',
            ]);
    }

    public function test_password_reset_informs_when_smtp_server_is_unreachable(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.host', 'smart.iagentesmtp.com.br');
        config()->set('mail.mailers.smtp.username', 'clerio@clerio.com.br');
        config()->set('mail.mailers.smtp.password', 'senha-real');
        config()->set('mail.from.address', 'cleriodias@gmail.com');

        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new TransportException('Connection could not be established with host "smart.iagentesmtp.com.br:587".'));

        $response = $this
            ->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'teste@example.com',
            ]);

        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors([
                'email' => 'Nao foi possivel conectar ao servidor SMTP configurado para enviar a redefinicao de senha. Verifique host, porta e liberacao de rede deste ambiente.',
            ]);
    }
}

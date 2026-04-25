<?php

namespace Tests\Feature;

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
}

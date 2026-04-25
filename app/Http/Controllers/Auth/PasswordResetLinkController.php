<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $this->ensureMailIsConfiguredForPasswordReset();

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            $status = Password::sendResetLink([
                'email' => Str::lower(trim((string) $request->input('email'))),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'email' => ['Nao foi possivel enviar o e-mail de redefinicao agora. Verifique a configuracao SMTP deste ambiente.'],
            ]);
        }

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    private function ensureMailIsConfiguredForPasswordReset(): void
    {
        $defaultMailer = Str::lower((string) config('mail.default', ''));

        if (in_array($defaultMailer, ['log', 'array'], true)) {
            throw ValidationException::withMessages([
                'email' => ['O envio de redefinicao de senha nao esta configurado para e-mail real neste ambiente.'],
            ]);
        }

        if ($defaultMailer !== 'smtp') {
            return;
        }

        $host = trim((string) config('mail.mailers.smtp.host', ''));
        $username = trim((string) config('mail.mailers.smtp.username', ''));
        $password = trim((string) config('mail.mailers.smtp.password', ''));
        $fromAddress = trim((string) config('mail.from.address', ''));

        if ($host === '' || $fromAddress === '') {
            throw ValidationException::withMessages([
                'email' => ['O envio de redefinicao de senha esta sem host SMTP ou remetente configurado.'],
            ]);
        }

        if (in_array($username, ['email_do_usuario_na_iagente', ''], true)
            || in_array($password, ['senha_do_usuario_na_iagente', ''], true)) {
            throw ValidationException::withMessages([
                'email' => ['O envio de redefinicao de senha esta sem as credenciais SMTP reais.'],
            ]);
        }
    }
}

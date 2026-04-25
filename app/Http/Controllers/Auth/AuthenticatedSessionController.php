<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\CashierClosure;
use App\Models\OnlineUser;
use App\Models\Unidade;
use App\Support\ActiveUnitSessionData;
use App\Support\ManagementScope;
use App\Support\PaymentControlNotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        if ($user->funcao_original === null) {
            $user->forceFill(['funcao_original' => $user->funcao])->save();
        }

        $funcaoOriginal = $user->funcao_original ?? $user->funcao;
        $funcaoAtual = (int) ($user->funcao ?? $funcaoOriginal);
        $unitId = (int) ($user->tb2_id ?? 0);

        if (ManagementScope::isBoss($user)) {
            $request->session()->forget('active_unit');
            $request->session()->put('active_role', $funcaoAtual);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        if (in_array((int) $funcaoOriginal, [5, 6], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Este perfil nao possui acesso ao sistema.',
            ]);
        }

        if ((int) $funcaoOriginal === 3) {
            $closedToday = CashierClosure::where('user_id', $user->id)
                ->whereDate('closed_date', Carbon::today())
                ->where(function ($query) use ($unitId) {
                    $query->whereNull('unit_id')
                        ->orWhere('unit_id', $unitId);
                })
                ->exists();

            if ($closedToday) {
                Auth::logout();

                throw ValidationException::withMessages([
                    'email' => 'Seu caixa ja foi fechado hoje para esta unidade. Novo acesso apenas amanha.',
                ]);
            }
        }

        if ($unitId <= 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Seu usuario nao possui uma loja vinculada.',
            ]);
        }

        $selectedUnit = Unidade::active()
            ->loginAllowed()
            ->select('tb2_id', 'tb2_nome', 'tb2_endereco', 'tb2_cnpj', 'tb2_tipo', 'matriz_id')
            ->with('matriz:id,nome')
            ->find($unitId);

        if (! $selectedUnit) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'A loja vinculada ao seu usuario esta inativa ou com login bloqueado.',
            ]);
        }

        $request->session()->put('active_unit', ActiveUnitSessionData::fromUnit($selectedUnit));
        $request->session()->put('active_role', $funcaoAtual);
        app(PaymentControlNotificationService::class)->notifyUserOnLogin($user, (int) $selectedUnit->tb2_id);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        OnlineUser::query()
            ->where('session_id', $request->session()->getId())
            ->delete();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

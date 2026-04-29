<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Aplicacao;
use App\Models\CashierClosure;
use App\Models\OnlineUser;
use App\Models\Unidade;
use App\Support\ActiveUnitSessionData;
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

        $funcaoOriginal = (int) ($user->funcao_original ?? $user->funcao);

        if ((int) $user->funcao !== $funcaoOriginal) {
            $user->forceFill(['funcao' => $funcaoOriginal])->save();
            $user->setAttribute('funcao', $funcaoOriginal);
        }

        $unitId = (int) ($user->tb2_id ?? 0);

        if ($funcaoOriginal === 7) {
            $request->session()->forget('active_unit');
            $request->session()->put('active_role', $funcaoOriginal);

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
            ->with([
                'matriz:id,nome,tb28_id',
                'matriz.aplicacao:tb28_id,tb28_rota_inicial',
            ])
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
        $request->session()->put('active_role', $funcaoOriginal);
        app(PaymentControlNotificationService::class)->notifyUserOnLogin($user, (int) $selectedUnit->tb2_id);

        return redirect()->intended($this->resolveLoginRedirectPath($selectedUnit));
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

    private function resolveLoginRedirectPath(Unidade $selectedUnit): string
    {
        $applicationId = (int) ($selectedUnit->matriz?->tb28_id ?? Aplicacao::PADARIA_NFE);
        $routeDefinition = (string) ($selectedUnit->matriz?->aplicacao?->tb28_rota_inicial
            ?: Aplicacao::defaultInitialRoute($applicationId));
        [$routeName, $queryParameters] = $this->parseLoginRouteDefinition($routeDefinition, $selectedUnit);

        if (! Route::has($routeName)) {
            $routeName = 'dashboard';
            $queryParameters = [];
        }

        return route(
            $routeName,
            array_merge($this->resolveLoginRedirectParameters($routeName, $selectedUnit), $queryParameters),
            false
        );
    }

    private function resolveLoginRedirectParameters(string $routeName, Unidade $selectedUnit): array
    {
        $route = Route::getRoutes()->getByName($routeName);

        if (! $route) {
            return [];
        }

        $parameters = [];

        foreach ($route->parameterNames() as $parameterName) {
            if (in_array($parameterName, ['unit', 'unit_id'], true)) {
                $parameters[$parameterName] = (int) $selectedUnit->tb2_id;
                continue;
            }

            if (in_array($parameterName, ['matriz', 'matriz_id', 'matrix'], true) && $selectedUnit->matriz_id) {
                $parameters[$parameterName] = (int) $selectedUnit->matriz_id;
            }
        }

        return $parameters;
    }

    private function parseLoginRouteDefinition(string $routeDefinition, Unidade $selectedUnit): array
    {
        $parts = explode('?', $routeDefinition, 2);
        $routeName = trim($parts[0]) !== '' ? trim($parts[0]) : 'dashboard';

        if (! isset($parts[1]) || trim($parts[1]) === '') {
            return [$routeName, []];
        }

        parse_str($parts[1], $queryParameters);

        foreach ($queryParameters as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $queryParameters[$key] = str_replace(
                ['{unit_id}', '{unit}', '{matriz_id}', '{matriz}', '{matrix}'],
                [
                    (string) $selectedUnit->tb2_id,
                    (string) $selectedUnit->tb2_id,
                    (string) ($selectedUnit->matriz_id ?? ''),
                    (string) ($selectedUnit->matriz_id ?? ''),
                    (string) ($selectedUnit->matriz_id ?? ''),
                ],
                $value
            );
        }

        return [$routeName, $queryParameters];
    }
}

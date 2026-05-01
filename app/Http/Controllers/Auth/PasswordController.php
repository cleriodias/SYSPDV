<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'regex:/^[0-9]+$/', 'confirmed'],
        ], [
            'password.required' => 'O campo senha e obrigatorio.',
            'password.regex' => 'A senha deve conter apenas numeros.',
            'password.confirmed' => 'A confirmacao da senha nao corresponde.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'matricule' => 'required|string',
            'password'  => 'required|string',
        ], [
            'matricule.required' => 'Le matricule PNMLS est obligatoire.',
            'password.required'  => 'Le mot de passe est obligatoire.',
        ]);

        if (Auth::attempt([
            'matricule_pnmls' => $request->matricule,
            'password'        => $request->password,
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'matricule' => 'Matricule ou mot de passe incorrect.',
        ])->onlyInput('matricule');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}

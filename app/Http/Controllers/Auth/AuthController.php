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


    /**
     * Handle logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Show the registration form.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:agents',
            'password' => 'required|string|min:8|confirmed',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string',
        ]);

        $agent = Agent::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'date_naissance' => $validated['date_naissance'],
            'lieu_naissance' => $validated['lieu_naissance'],
            'matricule_pnmls' => 'PNM-' . str_pad(Agent::count() + 1, 6, '0', STR_PAD_LEFT),
            'date_embauche' => now()->toDateString(),
            'statut' => 'actif',
        ]);

        Auth::login($agent);

        return redirect()->route('dashboard');
    }
}

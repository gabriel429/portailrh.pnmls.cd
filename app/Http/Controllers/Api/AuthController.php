<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        $request->session()->regenerate();

        return response()->json(['message' => 'Connexion reussie.']);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Deconnexion reussie.']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['agent', 'role']);

        return response()->json($user);
    }
}

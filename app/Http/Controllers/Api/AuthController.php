<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
                return response()->json(['message' => 'Identifiants incorrects.'], 401);
            }

            try {
                $request->session()->regenerate();
            } catch (\Throwable $e) {
                \Log::warning('Session regenerate failed: ' . $e->getMessage());
            }

            $user = Auth::user();
            $user->load(['agent', 'role']);

            return response()->json([
                'message' => 'Connexion reussie.',
                'user' => $user,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors normally
        } catch (\Throwable $e) {
            \Log::error('API login error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'message' => 'Erreur serveur lors de la connexion: ' . $e->getMessage(),
            ], 500);
        }
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

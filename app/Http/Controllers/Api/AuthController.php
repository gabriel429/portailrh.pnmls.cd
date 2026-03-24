<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Detect if a user-agent string comes from a mobile device.
     */
    private function isMobile(string $ua): bool
    {
        return (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|Opera Mini|IEMobile/i', $ua);
    }

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

            $user = Auth::user();
            $currentUA = $request->userAgent() ?? '';
            $currentIsMobile = $this->isMobile($currentUA);

            // Check existing active sessions for this user (same device type)
            $existingSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '>=', now()->subMinutes(config('session.lifetime', 120))->timestamp)
                ->get(['id', 'user_agent', 'ip_address']);

            foreach ($existingSessions as $session) {
                $sessionIsMobile = $this->isMobile($session->user_agent ?? '');

                // Same device type already has an active session → block
                if ($sessionIsMobile === $currentIsMobile) {
                    // Log the user back out since Auth::attempt already logged them in
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    $deviceType = $currentIsMobile ? 'téléphone' : 'ordinateur';

                    return response()->json([
                        'message' => "Votre compte est déjà connecté sur un autre {$deviceType}. Veuillez vous déconnecter de l'autre appareil ou contacter la Section Nouvelle Technologie.",
                    ], 409);
                }
            }

            try {
                $request->session()->regenerate();
            } catch (\Throwable $e) {
                \Log::warning('Session regenerate failed: ' . $e->getMessage());
            }

            // Safely load relations - don't crash if they fail
            try {
                $user->load(['agent', 'role']);
            } catch (\Throwable $e) {
                \Log::warning('User relations load failed: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Connexion reussie.',
                'user' => $user,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('API login error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'message' => 'Erreur serveur: ' . $e->getMessage(),
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

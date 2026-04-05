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

    /**
     * Extract device model from a user-agent string.
     */
    private function parseDeviceModel(string $ua): string
    {
        if (preg_match('/iPhone/i', $ua)) {
            if (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m)) {
                return 'iPhone (iOS ' . str_replace('_', '.', $m[1]) . ')';
            }
            return 'iPhone';
        }

        if (preg_match('/iPad/i', $ua)) {
            return 'iPad';
        }

        if (preg_match('/Android/i', $ua)) {
            if (preg_match('/;\s*([^;)]+)\s+Build/i', $ua, $m)) {
                return trim($m[1]);
            }
            if (preg_match('/Android\s[\d.]+;\s*([^;)]+)/i', $ua, $m)) {
                return trim($m[1]);
            }
            return 'Android';
        }

        // Desktop
        if (preg_match('/Edg\/([\d.]+)/i', $ua)) {
            $model = 'Microsoft Edge';
        } elseif (preg_match('/Chrome\/([\d.]+)/i', $ua)) {
            $model = 'Google Chrome';
        } elseif (preg_match('/Firefox\/([\d.]+)/i', $ua)) {
            $model = 'Mozilla Firefox';
        } elseif (preg_match('/Safari\/([\d.]+)/i', $ua) && !preg_match('/Chrome/i', $ua)) {
            $model = 'Safari';
        } else {
            $model = 'Navigateur';
        }

        if (preg_match('/Windows NT/i', $ua)) {
            $model .= ' (Windows)';
        } elseif (preg_match('/Macintosh/i', $ua)) {
            $model .= ' (Mac)';
        } elseif (preg_match('/Linux/i', $ua)) {
            $model .= ' (Linux)';
        }

        return $model;
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

            // Check if account is frozen
            if ($user->is_frozen) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => 'Votre compte a été gelé. Veuillez contacter la Section Nouvelle Technologie.',
                ], 403);
            }

            // Record login IP and timestamp
            $user->update([
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
            ]);

            // SuperAdmin bypasses concurrent session limits
            if ($user->is_super_admin) {
                try { $request->session()->regenerate(); } catch (\Throwable $e) {}
                return response()->json([
                    'message' => 'Connexion reussie.',
                    'user' => $user,
                ]);
            }

            $currentUA = $request->userAgent() ?? '';
            $currentIsMobile = $this->isMobile($currentUA);

            // Clean up expired sessions for this user first
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '<', now()->subMinutes(config('session.lifetime', 120))->timestamp)
                ->delete();

            // Check existing active sessions for this user (same device type)
            // Exclude current session — Auth::attempt() already assigned user_id to it
            $currentSessionId = session()->getId();
            $existingSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
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
                    $deviceModel = $this->parseDeviceModel($session->user_agent ?? '');
                    $deviceInfo = $deviceModel ? "{$deviceType} ({$deviceModel})" : $deviceType;

                    return response()->json([
                        'message' => "Votre compte est déjà connecté sur un autre {$deviceInfo}. Veuillez vous déconnecter de l'autre appareil ou contacter la Section Nouvelle Technologie.",
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
        $userId = Auth::id();
        $sessionId = session()->getId();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Explicitly remove the session from database to prevent ghost sessions
        DB::table('sessions')->where('id', $sessionId)->delete();

        return response()->json(['message' => 'Deconnexion reussie.']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['agent.departement', 'agent.province', 'role']);

        $data = $user->toArray();

        // Expose super admin flag only to the super admin themselves
        if ($user->is_super_admin) {
            $data['is_super_admin'] = true;
        }

        // Expose permission codes for the frontend
        $permissions = collect();
        if ($user->role) {
            $permissions = $user->role->permissions()->pluck('code');
        }
        if ($user->agent) {
            $agentPerms = $user->agent->permissions()->pluck('code');
            $permissions = $permissions->merge($agentPerms)->unique();
        }
        $data['permissions'] = $permissions->values()->toArray();

        return response()->json($data);
    }
}

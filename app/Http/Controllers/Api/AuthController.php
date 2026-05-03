<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

            if ($user->is_frozen) {
                Auth::guard('web')->logout();
                try {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                } catch (\Throwable $e) {
                }

                return response()->json([
                    'message' => 'Votre compte a ete gele. Veuillez contacter la Section Nouvelle Technologie.',
                ], 403);
            }

            $user->update([
                'last_login_ip' => $request->ip(),
                'last_login_at' => now(),
            ]);

            if ($user->is_super_admin) {
                try {
                    $request->session()->regenerate();
                } catch (\Throwable $e) {
                }

                return response()->json([
                    'message' => 'Connexion reussie.',
                    'user' => $user,
                ]);
            }

            $currentUA = $request->userAgent() ?? '';
            $currentIp = $request->ip();
            $currentIsMobile = $this->isMobile($currentUA);

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '<', now()->subMinutes(config('session.lifetime', 120))->timestamp)
                ->delete();

            $currentSessionId = null;
            try {
                $currentSessionId = session()->getId();
            } catch (\Throwable $e) {
            }

            $existingSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->get(['id', 'user_agent', 'ip_address']);

            foreach ($existingSessions as $session) {
                $sessionUserAgent = $session->user_agent ?? '';
                $sessionIpAddress = $session->ip_address ?? '';
                $sessionIsMobile = $this->isMobile($sessionUserAgent);

                $sameDeviceType = $sessionIsMobile === $currentIsMobile;
                $sameBrowser = $sessionUserAgent === $currentUA;
                $sameIpAddress = $sessionIpAddress === $currentIp;

                // Ghost session from the same browser on the same machine:
                // replace it instead of blocking the user with a false conflict.
                if ($sameDeviceType && $sameBrowser && $sameIpAddress) {
                    DB::table('sessions')->where('id', $session->id)->delete();
                    continue;
                }

                if ($sameDeviceType) {
                    Auth::guard('web')->logout();
                    try {
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                    } catch (\Throwable $e) {
                    }

                    $deviceType = $currentIsMobile ? 'telephone' : 'ordinateur';
                    $deviceModel = $this->parseDeviceModel($sessionUserAgent);
                    $deviceInfo = $deviceModel ? "{$deviceType} ({$deviceModel})" : $deviceType;

                    return response()->json([
                        'message' => "Votre compte est deja connecte sur un autre {$deviceInfo}. Veuillez vous deconnecter de l'autre appareil ou contacter la Section Nouvelle Technologie.",
                    ], 409);
                }
            }

            try {
                $request->session()->regenerate();
            } catch (\Throwable $e) {
                \Log::warning('Session regenerate failed: ' . $e->getMessage());
            }

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
        $sessionId = null;
        try {
            $sessionId = session()->getId();
        } catch (\Throwable $e) {
        }

        Auth::guard('web')->logout();
        try {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (\Throwable $e) {
        }

        if ($sessionId) {
            DB::table('sessions')->where('id', $sessionId)->delete();
        }

        return response()->json(['message' => 'Deconnexion reussie.']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['agent.departement', 'agent.province', 'role']);

        $data = $user->toArray();

        if ($user->is_super_admin) {
            $data['is_super_admin'] = true;
        }

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

    public function mobileLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        if ($user->is_frozen) {
            return response()->json([
                'message' => 'Votre compte a ete gele. Veuillez contacter la Section Nouvelle Technologie.',
            ], 403);
        }

        $user->update([
            'last_login_ip' => $request->ip(),
            'last_login_at' => now(),
        ]);

        $user->tokens()->where('name', $request->device_name)->delete();

        $token = $user->createToken($request->device_name)->plainTextToken;

        $user->load(['agent.departement', 'agent.province', 'role']);

        return response()->json([
            'message' => 'Connexion reussie.',
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function mobileRegister(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'message' => 'Inscription reussie.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function mobileLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Deconnexion reussie.']);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminNTMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acces refuse.'], 403);
            }
            abort(403, 'Accès refusé.');
        }

                // SuperAdmin bypasses all role checks
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->role) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acces refuse.'], 403);
            }
            abort(403, 'Accès refusé.');
        }

        $role = strtolower(trim((string) $user->role->nom_role));
        $allowed = [
            'section nouvelle technologie',
            'chef section nouvelle technologie',
            'chef de section nouvelle technologie',
        ];

        if (!in_array($role, $allowed, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acces reserve a la Section Nouvelle Technologie.'], 403);
            }
            abort(403, 'Accès réservé à la Section Nouvelle Technologie.');
        }

        return $next($request);
    }
}

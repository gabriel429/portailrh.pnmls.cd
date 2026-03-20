<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || empty($roles)) {
            return $next($request);
        }

        // Check if user has any of the required roles
        $hasRole = false;
        foreach ($roles as $role) {
            if (
                $user->role
                && $user->role->nom_role
                && strtolower(trim((string) $user->role->nom_role)) === strtolower(trim((string) $role))
            ) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acces refuse.'], 403);
            }
            abort(403, 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
        }

        return $next($request);
    }
}

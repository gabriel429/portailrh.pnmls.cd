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

        if (!$user || !$user->role) {
            abort(403, 'Accès refusé.');
        }

        $role = strtolower(trim((string) $user->role->nom_role));
        $allowed = [
            'chef section nouvelle technologie',
            'chef de section nouvelle technologie',
        ];

        if (!in_array($role, $allowed, true)) {
            abort(403, 'Accès réservé au Chef de Section Nouvelle Technologie.');
        }

        return $next($request);
    }
}

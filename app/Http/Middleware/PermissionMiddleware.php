<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If no specific permissions required, just check auth
        if (empty($permissions)) {
            return $next($request);
        }

        // Check if user's role has any of the required permissions
        $agent = $user->agent ?? $user;
        $userPermissions = $agent->permissions ?? collect();

        foreach ($permissions as $permission) {
            if ($userPermissions->contains('nom_permission', $permission)) {
                return $next($request);
            }
        }

        abort(403, 'Vous n\'avez pas la permission requise.');
    }
}

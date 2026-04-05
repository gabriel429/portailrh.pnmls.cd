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

        // SuperAdmin bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // If no specific permissions required, just check auth
        if (empty($permissions)) {
            return $next($request);
        }

        // Collect permissions from role + direct agent permissions
        $rolePermissions = $user->role?->permissions?->pluck('code') ?? collect();
        $agentPermissions = $user->agent?->permissions()?->pluck('code') ?? collect();
        $allCodes = $rolePermissions->merge($agentPermissions)->unique();

        foreach ($permissions as $permission) {
            if ($allCodes->contains($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Vous n\'avez pas la permission requise.');
    }
}

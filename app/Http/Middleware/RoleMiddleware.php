<?php

namespace App\Http\Middleware;

use App\Services\RoleService;
use App\Services\UserDataScope;
use App\Support\RoleNames;
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

        // SuperAdmin bypasses all role checks
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has any of the required roles
        $hasRole = RoleNames::matches($user->role?->nom_role, $roles);
        if (!$hasRole && app(RoleService::class)->isAssistantRh($user)) {
            $hasRole = RoleNames::matches('Assistant RH', $roles);
        }

        if (!$hasRole && $this->allowsLocalScopedAccess($request, $user)) {
            $hasRole = true;
        }

        if (!$hasRole) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acces refuse.'], 403);
            }
            abort(403, 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
        }

        return $next($request);
    }

    private function allowsLocalScopedAccess(Request $request, $user): bool
    {
        if (!app(UserDataScope::class)->isLocalUser($user)) {
            return false;
        }

        if ($request->is('api/pointages*') || $request->is('api/v1/pointages*')) {
            return true;
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $request->is('api/agents*')
                || $request->is('api/v1/agents*')
                || $request->is('api/signalements*')
                || $request->is('api/v1/signalements*');
        }

        return $request->is('api/signalements*')
            || $request->is('api/v1/signalements*');
    }
}

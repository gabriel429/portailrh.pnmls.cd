<?php

namespace App\Http\Middleware;

use App\Services\RoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotAssistantRh
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && app(RoleService::class)->isAssistantRh($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Action reservee a la Section Ressources Humaines.',
                ], 403);
            }

            abort(403, 'Action reservee a la Section Ressources Humaines.');
        }

        return $next($request);
    }
}

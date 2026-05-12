<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ManageDocumentsTravailMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifie.'], 401);
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        $role = $this->normalize($user->role?->nom_role);
        $agent = $user->agent;
        $organe = $this->normalize($agent?->organe);
        $profile = $this->normalize(trim(($agent?->fonction ?? '') . ' ' . ($agent?->poste_actuel ?? '')));

        $isProvinceScope = str_contains($role, 'provincial')
            || str_contains($role, 'province')
            || str_contains($organe, 'provincial');

        $isAssistant = str_contains($role, 'assistant')
            || str_contains($profile, 'assistant rh')
            || str_contains($profile, 'assistant ressources humaines')
            || str_contains($profile, 'assistant ressource humaine');

        $allowedRoles = [
            'section ressources humaines',
            'section ressource humaine',
            'chef section rh',
            'chef de section rh',
            'chef section ressources humaines',
            'rh national',
            'ressources humaines',
            'rh',
        ];

        if (!$isProvinceScope && !$isAssistant && in_array($role, $allowedRoles, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'Acces reserve a la Section RH.'], 403);
    }

    private function normalize(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }
}

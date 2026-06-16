<?php

namespace App\Support;

use Illuminate\Support\Str;

class RoleNames
{
    public static function matches(?string $role, string|array $expectedRoles): bool
    {
        $expectedRoles = is_array($expectedRoles) ? $expectedRoles : [$expectedRoles];
        $role = self::canonical($role);

        foreach ($expectedRoles as $expectedRole) {
            if ($role === self::canonical((string) $expectedRole)) {
                return true;
            }
        }

        return false;
    }

    public static function canonical(?string $role): string
    {
        $role = self::normalize($role);

        if (preg_match('/\brh\s+provincial(e)?\b/', $role) === 1
            || preg_match('/\bressources?\s+humaines?\s+provincial(e)?\b/', $role) === 1
        ) {
            return 'rh provincial';
        }

        if (preg_match('/\brh\s+national(e)?\b/', $role) === 1
            || preg_match('/\bressources?\s+humaines?\s+national(e)?\b/', $role) === 1
        ) {
            return 'rh national';
        }

        return match ($role) {
            'rh',
            'responsable rh',
            'section rh',
            'ressource humaine',
            'ressources humaines',
            'section ressource humaine',
            'section ressources humaines' => 'section ressources humaines',
            'chef section rh',
            'chef de section rh',
            'chef section ressource humaine',
            'chef section ressources humaines',
            'chef de section ressource humaine',
            'chef de section ressources humaines' => 'chef section rh',
            default => $role,
        };
    }

    private static function normalize(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }
}

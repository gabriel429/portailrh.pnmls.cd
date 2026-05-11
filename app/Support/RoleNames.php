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

        return match ($role) {
            'ressource humaine',
            'ressources humaines',
            'section ressource humaine',
            'section ressources humaines' => 'section ressources humaines',
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

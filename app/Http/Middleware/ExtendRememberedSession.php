<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtendRememberedSession
{
    public const SESSION_KEY = 'remember_login';
    public const SESSION_MARKED_AT_KEY = 'remember_login_at';

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hasRememberedSession($request)) {
            config(['session.lifetime' => $this->rememberLifetime()]);
        }

        return $next($request);
    }

    private function hasRememberedSession(Request $request): bool
    {
        try {
            return $request->hasSession(false)
                && $request->session()->get(self::SESSION_KEY) === true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function rememberLifetime(): int
    {
        return max(
            (int) config('session.lifetime', 120),
            (int) config('session.remember_lifetime', 43200)
        );
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    private const CACHE_CLEAN_VERSION = '2026-05-07-forum-mobile-build-cache-v1';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security Headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Strict Transport Security (HTTPS only)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy - Relaxed for development
        $csp = env('APP_ENV') === 'production'
            ? "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self' https:; font-src 'self';"
            : "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-eval' 'unsafe-inline'; connect-src 'self' https: ws: wss:; font-src 'self';";

        $response->headers->set('Content-Security-Policy', $csp);

        // PWA Cache Control
        if (str_contains($request->path(), 'sw.js') || str_contains($request->path(), 'manifest')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        $isHtmlResponse = str_contains($contentType, 'text/html');

        if ($isHtmlResponse && $request->cookie('epnmls_cache_cleaned') !== self::CACHE_CLEAN_VERSION) {
            $response->headers->set('Clear-Site-Data', '"cache"');
            $response->headers->setCookie(cookie(
                'epnmls_cache_cleaned',
                self::CACHE_CLEAN_VERSION,
                60 * 24 * 30,
                '/',
                null,
                $request->secure(),
                false,
                false,
                'Lax'
            ));
        }

        return $response;
    }
}

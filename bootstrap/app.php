<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/web_rh.php'));

            // SPA catch-all — MUST be registered AFTER all other routes
            \Illuminate\Support\Facades\Route::middleware('web')
                ->get('/{any}', function () {
                    return view('spa');
                })->where('any', '^(?!api/).*$');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Global security middleware
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'role'        => \App\Http\Middleware\RoleMiddleware::class,
            'permission'  => \App\Http\Middleware\PermissionMiddleware::class,
            'admin.nt'    => \App\Http\Middleware\AdminNTMiddleware::class,
            'super.admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ALWAYS return JSON for API routes — catch everything
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                $status = 500;
                if (method_exists($e, 'getStatusCode')) {
                    $status = $e->getStatusCode();
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                }

                // Ne jamais exposer les détails de l'erreur en production
                $message = app()->isLocal() || app()->environment('testing')
                    ? ($e->getMessage() ?: 'Server Error')
                    : match (true) {
                        $status === 401 => 'Non authentifié.',
                        $status === 403 => 'Accès refusé.',
                        $status === 404 => 'Ressource introuvable.',
                        $status === 419 => 'Session expirée.',
                        $status === 429 => 'Trop de requêtes.',
                        default => 'Erreur interne du serveur.',
                    };

                return response()->json([
                    'message' => $message,
                ], $status);
            }
        });
    })->create();

// Allow desktop app to redirect writable paths to %APPDATA%
// env() works here because PHP inherits process environment variables via getenv()
if ($storagePath = env('APP_STORAGE_PATH')) {
    $app->useStoragePath($storagePath);
}
if ($bootstrapCachePath = env('APP_BOOTSTRAP_CACHE_PATH')) {
    $app->useBootstrapPath(dirname($bootstrapCachePath));
}

return $app;

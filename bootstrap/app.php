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
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'role'     => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'admin.nt' => \App\Http\Middleware\AdminNTMiddleware::class,
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
                return response()->json([
                    'message' => $e->getMessage() ?: 'Server Error',
                ], $status);
            }
        });
    })->create();

// Allow desktop app to redirect storage to a writable location (e.g. %APPDATA%)
if ($storagePath = env('APP_STORAGE_PATH')) {
    $app->useStoragePath($storagePath);
}

return $app;

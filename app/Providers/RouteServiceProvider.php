<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\Agent;
use App\Models\Role;
use App\Models\Document;
use App\Models\Request as RequestModel;
use App\Models\Signalement;
use App\Models\Pointage;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        Route::pattern('id', '[0-9]+');

        // Implicit model binding
        Route::model('agent', Agent::class);
        Route::model('document', Document::class);
        Route::model('request', RequestModel::class);
        Route::model('signalement', Signalement::class);
        Route::model('pointage', Pointage::class);
        Route::model('role', Role::class);

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}

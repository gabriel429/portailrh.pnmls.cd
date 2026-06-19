<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MonitoringService;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // Enable query logging in development
        if (app()->environment('local')) {
            \DB::enableQueryLog();
        }

        $response = $next($request);

        $executionTime = microtime(true) - $startTime;

        // Log API requests
        if ($request->is('api/*')) {
            MonitoringService::logApiRequest($request, $response, $executionTime);
        }

        // Log slow requests
        if ($executionTime > 2) {
            \Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => round($executionTime, 3) . 's',
                'queries' => app()->environment('local') ? \DB::getQueryLog() : null,
            ]);
        }

        // Add performance headers
        $response->headers->set('X-Response-Time', round($executionTime * 1000, 2) . 'ms');

        return $response;
    }
}
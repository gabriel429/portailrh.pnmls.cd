<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MonitoringService
{
    /**
     * Log API request
     */
    public static function logApiRequest(Request $request, $response, float $executionTime)
    {
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
            'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB',
        ];

        // Log slow requests
        if ($executionTime > 1) {
            Log::warning('Slow API request detected', $logData);
        }

        // Log to dedicated API channel
        Log::channel('api')->info('API Request', $logData);
    }

    /**
     * Log application errors with context
     */
    public static function logError(\Throwable $exception, array $context = [])
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ];

        Log::error('Application Error', $errorData);

        // Send critical errors to admin (if needed)
        if (self::isCriticalError($exception)) {
            self::notifyAdmins($errorData);
        }
    }

    /**
     * Check database performance
     */
    public static function checkDatabasePerformance()
    {
        $slowQueries = DB::table('information_schema.processlist')
                ->where('command', '!=', 'Sleep')
                ->where('time', '>', 5)
                ->get();

            if ($slowQueries->count() > 0) {
                Log::warning('Slow database queries detected', [
                    'count' => $slowQueries->count(),
                    'queries' => $slowQueries->toArray()
                ]);
            }

        return [
            'slow_queries' => $slowQueries->count(),
            'active_connections' => DB::table('information_schema.processlist')->count(),
        ];
    }

    /**
     * Get system health metrics
     */
    public static function getHealthMetrics()
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'database' => [
                'connected' => DB::connection()->getPdo() !== null,
                'migrations' => DB::table('migrations')->count(),
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'connected' => true, // Implement actual cache connection check
            ],
            'storage' => [
                'disk_free' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . 'GB',
                'disk_total' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2) . 'GB',
            ],
            'memory' => [
                'usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
                'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB',
                'limit' => ini_get('memory_limit'),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'laravel' => [
                'version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
            ],
        ];
    }

    /**
     * Check if error is critical
     */
    private static function isCriticalError(\Throwable $exception): bool
    {
        $criticalExceptions = [
            \PDOException::class,
            \Illuminate\Database\QueryException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
        ];

        foreach ($criticalExceptions as $criticalException) {
            if ($exception instanceof $criticalException) {
                return true;
            }
        }

        return false;
    }

    /**
     * Notify administrators about critical errors
     */
    private static function notifyAdmins(array $errorData)
    {
        // Implement notification logic (email, SMS, etc.)
        // For now, just log it
        Log::channel('critical')->error('CRITICAL ERROR - Admin notification required', $errorData);
    }

    /**
     * Log user activity
     */
    public static function logUserActivity(int $userId, string $action, array $data = [])
    {
        DB::table('activity_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
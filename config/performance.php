<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour les optimisations de performance et le monitoring
    |
    */

    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING', true),
        'slow_request_threshold' => 2, // seconds
        'slow_query_threshold' => 1000, // milliseconds
    ],

    'cache' => [
        'agents_list_ttl' => 3600, // 1 heure
        'departments_ttl' => 86400, // 24 heures
        'provinces_ttl' => 86400, // 24 heures
        'permissions_ttl' => 3600, // 1 heure
    ],

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    'optimization' => [
        'eager_loading' => true,
        'query_caching' => true,
        'compress_responses' => env('COMPRESS_RESPONSES', true),
    ],

    'limits' => [
        'max_upload_size' => 10485760, // 10MB
        'max_export_rows' => 10000,
        'max_api_requests_per_minute' => 60,
    ],
];
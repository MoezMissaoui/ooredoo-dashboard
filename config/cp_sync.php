<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Club Privilèges Synchronisation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la synchronisation avec l'API Club Privilèges
    |
    */

    'base_url' => env('CP_SYNC_BASE_URL', 'https://clubprivileges.app'),
    'sync_endpoint' => env('CP_SYNC_ENDPOINT', '/api/sync-dashboard-data'),
    
    // Authentification API par token (utilise CP_EXPORT_TOKEN existant)
    'api_token' => env('CP_EXPORT_TOKEN', 'cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789'),
    
    // Anciens identifiants (maintenus pour compatibilité)
    'server_username' => env('CP_SYNC_SERVER_USERNAME'),
    'server_password' => env('CP_SYNC_SERVER_PASSWORD'),
    'username' => env('CP_SYNC_USERNAME'),
    'password' => env('CP_SYNC_PASSWORD'),
    
    'timeout' => env('CP_SYNC_TIMEOUT', 300), // 5 minutes
    
    'enabled' => env('CP_SYNC_ENABLED', true),
    
    'schedule' => [
        'enabled' => env('CP_SYNC_SCHEDULE_ENABLED', true),
        'frequency' => env('CP_SYNC_SCHEDULE_FREQUENCY', 'hourly'), // hourly, daily, custom
        'custom_cron' => env('CP_SYNC_CUSTOM_CRON', '0 * * * *'), // Toutes les heures
    ],
    
    'cache' => [
        'last_result_ttl' => env('CP_SYNC_CACHE_LAST_RESULT_TTL', 86400), // 24h
        'history_ttl' => env('CP_SYNC_CACHE_HISTORY_TTL', 604800), // 7 jours
        'history_limit' => env('CP_SYNC_CACHE_HISTORY_LIMIT', 100),
    ],
    
    'retry' => [
        'enabled' => env('CP_SYNC_RETRY_ENABLED', true),
        'max_attempts' => env('CP_SYNC_RETRY_MAX_ATTEMPTS', 3),
        'delay_seconds' => env('CP_SYNC_RETRY_DELAY_SECONDS', 60),
    ],
    
    'notifications' => [
        'enabled' => env('CP_SYNC_NOTIFICATIONS_ENABLED', false),
        'email' => env('CP_SYNC_NOTIFICATION_EMAIL'),
        'slack_webhook' => env('CP_SYNC_SLACK_WEBHOOK'),
    ],
];

<?php

return [
    'endpoint' => env('CP_EXPORT_URL', 'https://clubprivileges.app/api/get-pending-sync-data'),
    'token'    => env('CP_EXPORT_TOKEN', 'cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789'),
    'timeout'  => env('CP_EXPORT_TIMEOUT', 300), // 5 minutes
    'retry_attempts' => env('CP_EXPORT_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('CP_EXPORT_RETRY_DELAY', 5), // secondes
    
    // Mapping des tables et leurs clés primaires
    'tables' => [
        'client'                => 'client_id',
        'client_abonnement'     => 'client_abonnement_id',
        'history'               => 'history_id',
        'promotion_pass_orders' => 'id',
        'promotion_pass_vendu'  => 'id',
        'partner'               => 'partner_id',
        'promotion'             => 'promotion_id',
    ],
    
    // Configuration des tables de destination (si différentes)
    'destination_tables' => [
        'client'                => 'clients',
        'client_abonnement'     => 'client_abonnements',
        'history'               => 'histories',
        'promotion_pass_orders' => 'promotion_pass_orders',
        'promotion_pass_vendu'  => 'promotion_pass_vendus',
        'partner'               => 'partners',
        'promotion'             => 'promotions',
    ],
];

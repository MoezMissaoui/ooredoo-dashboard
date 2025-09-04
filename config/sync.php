<?php

return [
    // API source d'extraction
    'url' => env('SYNC_API_URL', 'https://clubprivileges.app/api/get-pending-sync-data'),
    'token' => env('SYNC_API_TOKEN', ''),

    // Taille de lot et timeouts
    'batch_size' => env('SYNC_BATCH_SIZE', 5000),
    'timeout' => env('SYNC_HTTP_TIMEOUT', 30),
    'retry' => [
        'times' => env('SYNC_RETRY_TIMES', 3),
        'sleep_ms' => env('SYNC_RETRY_SLEEP_MS', 1000),
    ],

    // Mapping table -> colonne PK locale
    'tables' => [
        'partner'                 => 'partner_id',
        'promotion'               => 'promotion_id',
        'client'                  => 'client_id',
        'client_abonnement'       => 'client_abonnement_id',
        'promotion_pass_orders'   => 'id',
        'promotion_pass_vendu'    => 'id',
        'history'                 => 'history_id',
    ],
];




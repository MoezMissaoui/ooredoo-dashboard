<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Eklektik API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration avec l'API Eklektik
    | Documentation: https://payment.eklectic.tn/API/docs/#/
    |
    */

    'api_url' => env('EKLEKTIK_API_URL', 'https://payment.eklectic.tn/API'),
    
    'client_id' => env('EKLEKTIK_CLIENT_ID', '0a2e605d-88f6-11ec-9feb-fa163e3dd8b3'),
    
    'client_secret' => env('EKLEKTIK_CLIENT_SECRET', 'ee60bb148a0e468a5053f9db41008780'),
    
    'offer_ids' => [
        'tt' => env('EKLEKTIK_OFFER_ID_TT', 11),
        'orange' => env('EKLEKTIK_OFFER_ID_ORANGE', 12),
        'promo_3_days' => env('EKLEKTIK_OFFER_ID_3_DAYS', 82),
        'promo_15_days' => env('EKLEKTIK_OFFER_ID_15_DAYS', 87), 
        'promo_30_days' => env('EKLEKTIK_OFFER_ID_30_DAYS', 88),
    ],
    
    // Liste complète des Offer IDs pour les tests
    'all_offer_ids' => [11, 12, 82, 87, 88],
    
    'cache_ttl' => env('EKLEKTIK_CACHE_TTL', 300), // 5 minutes
    
    'timeout' => env('EKLEKTIK_TIMEOUT', 30), // 30 seconds
    
    'retry_attempts' => 3,
    
    'endpoints' => [
        'auth' => '/oauth/token',
        'subscribers' => '/subscription/subscribers',
        'find_subscription' => '/subscription/find',
        'subscription_detail' => '/subscription',
        'send_sms' => '/subscription/sendmt',
        'oneshot' => '/subscription/oneshot'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Field Mapping
    |--------------------------------------------------------------------------
    |
    | Mappage des champs entre l'API Eklektik et notre structure interne
    |
    */
    'field_mapping' => [
        'phone_number' => 'phone_number',
        'service_type' => 'service_type',
        'status' => 'status',
        'created_at' => 'created_at',
        'last_activity' => 'last_activity',
        'usage_count' => 'usage_count',
        'usage_percentage' => 'usage_percentage'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Service Type Mapping
    |--------------------------------------------------------------------------
    |
    | Mappage des types de services
    |
    */
    'service_types' => [
        'SMS' => 'SUBSCRIPTION',
        'USSD' => 'PROMOTION', 
        'VOICE' => 'NOTIFICATION',
        'DATA' => 'SUBSCRIPTION'
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Status Mapping
    |--------------------------------------------------------------------------
    |
    | Mappage des statuts
    |
    */
    'status_mapping' => [
        'active' => 'ACTIVE',
        'inactive' => 'INACTIVE',
        'pending' => 'PENDING',
        'suspended' => 'INACTIVE'
    ]
];

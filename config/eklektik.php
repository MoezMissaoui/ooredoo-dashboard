<?php

return [
    'eklectic' => [
        'uri' => [
            'prefix' => env('EKLECTIC_ENDPOINT_PREFIX', 'API'),
            'host' => env('EKLECTIC_ENDPOINT_HOST', 'payment.eklectic.tn'),
            'protocol' => env('EKLECTIC_ENDPOINT_PROTOCOL', 'https'),
            'port' => env('EKLECTIC_ENDPOINT_PORT', '443'),
        ],
        'client_id' => env('EKLECTIC_ENDPOINT_CLIENT_ID', '0a2e605d-88f6-11ec-9feb-fa163e3dd8b3'),
        'client_secret' => env('EKLECTIC_ENDPOINT_CLIENT_SECRET', 'ee60bb148a0e468a5053f9db41008780'),
        'grant_type' => env('EKLECTIC_ENDPOINT_GRANT_TYPE', 'client_credentials'),
        'tt_offer_id' => env('EKLECTIC_ENDPOINT_TT_OFFER_ID', '11'),
        'orange_offer_id' => env('EKLECTIC_ENDPOINT_ORANGE_OFFER_ID', '82'),
        'taraji_offer_id' => env('EKLECTIC_ENDPOINT_TARAJI_OFFER_ID', '26'),
        'cache_ttl' => (int) env('EKLECTIC_CACHE_TTL', 300),
        'timeout' => (int) env('EKLECTIC_TIMEOUT', 30),
    ],
    
    // Configuration pour l'API
    'api_url' => 'https://payment.eklectic.tn/API',
    
    'client_id' => '0a2e605d-88f6-11ec-9feb-fa163e3dd8b3',
    
    'client_secret' => 'ee60bb148a0e468a5053f9db41008780',
    
    'offer_ids' => [
        'tt' => '11',
        'orange' => '82',
        'taraji' => '26',
    ],
    
    'cache_ttl' => (int) env('EKLEKTIK_CACHE_TTL', 300),
    
    'timeout' => (int) env('EKLEKTIK_TIMEOUT', 30),
    
    'endpoints' => [
        'auth' => '/oauth/token',
        'subscribers' => '/subscription/subscribers',
        'subscription_find' => '/subscription/find',
        'subscription_otp' => '/subscription/otp',
        'subscription_confirm' => '/subscription/confirm',
        'subscription_oneClick' => '/subscription/oneClick',
        'subscription_token' => '/subscription/token',
        'subscription_sendmt' => '/subscription/sendmt',
        'subscription_oneshot' => '/subscription/oneshot',
    ],
];
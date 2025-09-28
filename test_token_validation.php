<?php

echo "🔍 Test de validation du token Club Privilèges\n";
echo "============================================\n\n";

// Test avec différents formats de token
$tokens = [
    'cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789',
    'Bearer cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789',
    'aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789',
    'test-token',
    'cp_dashboard_test'
];

$url = 'https://clubprivileges.app/api/get-pending-sync-data';
$payload = [
    'tables' => [
        'client' => [
            'colonne_id_name' => 'client_id',
            'last_inserted_id' => 0
        ]
    ]
];

foreach ($tokens as $index => $token) {
    echo ($index + 1) . ". Test avec token: " . substr($token, 0, 30) . "...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_VERBOSE, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "   - HTTP Code: $httpCode\n";
    if ($httpCode === 200) {
        echo "   - ✅ SUCCÈS! Token valide\n";
        echo "   - Response: " . substr($response, 0, 200) . "\n";
        break;
    } elseif ($httpCode === 401) {
        echo "   - ❌ Token invalide (401 Unauthorized)\n";
    } elseif ($httpCode === 500) {
        echo "   - ⚠️ Erreur serveur (500) - Token peut être valide mais problème côté serveur\n";
    } else {
        echo "   - ❌ Erreur: $httpCode\n";
    }
    echo "\n";
}

// Test avec un payload différent
echo "6. Test avec payload différent (toutes les tables):\n";
$fullPayload = [
    'tables' => [
        'client' => [
            'colonne_id_name' => 'client_id',
            'last_inserted_id' => 0
        ],
        'partner' => [
            'colonne_id_name' => 'partner_id',
            'last_inserted_id' => 0
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fullPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   - HTTP Code: $httpCode\n";
echo "   - Response: " . substr($response, 0, 200) . "\n";

echo "\n✅ Tests de validation terminés.\n";

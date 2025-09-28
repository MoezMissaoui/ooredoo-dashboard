<?php

echo "🔍 Test de l'API Club Privilèges avec cURL\n";
echo "==========================================\n\n";

// Configuration
$url = 'https://clubprivileges.app/api/get-pending-sync-data';
$token = 'cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789';

// Payload minimal
$payload = [
    'tables' => [
        'client' => [
            'colonne_id_name' => 'client_id',
            'last_inserted_id' => 0
        ]
    ]
];

echo "📡 URL: $url\n";
echo "🔑 Token: " . substr($token, 0, 20) . "...\n";
echo "📦 Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Test 1: Token direct
echo "1. Test avec token direct:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   - HTTP Code: $httpCode\n";
echo "   - Response: " . substr($response, 0, 500) . "\n";
if ($error) {
    echo "   - Error: $error\n";
}
echo "\n";

// Test 2: Bearer token
echo "2. Test avec Bearer token:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   - HTTP Code: $httpCode\n";
echo "   - Response: " . substr($response, 0, 500) . "\n";
if ($error) {
    echo "   - Error: $error\n";
}
echo "\n";

// Test 3: GET request (au cas où ce serait un GET)
echo "3. Test avec GET request:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query(['tables' => json_encode($payload['tables'])]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   - HTTP Code: $httpCode\n";
echo "   - Response: " . substr($response, 0, 500) . "\n";
if ($error) {
    echo "   - Error: $error\n";
}

echo "\n✅ Tests terminés.\n";

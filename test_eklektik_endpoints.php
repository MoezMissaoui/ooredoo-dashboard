<?php

// Script simple pour tester l'API Eklektik
$url = 'http://127.0.0.1:8000/api/eklektik/test-all-endpoints';

echo "🔍 Test des endpoints Eklektik selon la documentation...\n\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Accept: application/json',
        'timeout' => 60
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Erreur lors de la récupération des données\n";
    exit(1);
}

$data = json_decode($response, true);

if (!$data) {
    echo "❌ Erreur de décodage JSON\n";
    echo "Réponse brute: " . $response . "\n";
    exit(1);
}

echo "✅ Données récupérées avec succès!\n\n";

// Analyse de l'authentification
echo "🔐 AUTHENTIFICATION:\n";
echo "Status: " . ($data['auth']['status'] ?? 'N/A') . "\n";
echo "Succès: " . ($data['auth']['success'] ? 'OUI' : 'NON') . "\n";
echo "Token reçu: " . ($data['auth']['token_received'] ? 'OUI' : 'NON') . "\n";
echo "Longueur token: " . ($data['auth']['token_length'] ?? 'N/A') . "\n\n";

// Analyse des endpoints
echo "📡 ENDPOINTS TESTÉS:\n";
if (isset($data['endpoints'])) {
    foreach ($data['endpoints'] as $name => $endpoint) {
        $status = $endpoint['status'] ?? 'N/A';
        $success = $endpoint['success'] ? '✅' : '❌';
        echo "  {$success} {$name}: {$status}\n";
    }
} else {
    echo "  Aucun endpoint testé\n";
}
echo "\n";

// Analyse des tests de paramètres
echo "🧪 TESTS AVEC PARAMÈTRES:\n";
if (isset($data['parameter_tests'])) {
    foreach ($data['parameter_tests'] as $name => $test) {
        $status = $test['status'] ?? 'N/A';
        $success = $test['success'] ? '✅' : '❌';
        $hasData = $test['has_data'] ? '📊' : '📭';
        $method = $test['method'] ?? 'GET';
        echo "  {$success} {$hasData} [{$method}] {$name}: {$status}\n";
        
        if ($test['has_data'] ?? false) {
            echo "    Données: {$test['data_count']} éléments (type: {$test['response_type']})\n";
        }
        
        if (isset($test['error'])) {
            echo "    Erreur: {$test['error']}\n";
        }
    }
} else {
    echo "  Aucun test de paramètres\n";
}
echo "\n";

// Résumé
echo "📊 RÉSUMÉ:\n";
if (isset($data['summary'])) {
    $summary = $data['summary'];
    echo "Endpoints testés: {$summary['total_endpoints_tested']}\n";
    echo "Endpoints réussis: {$summary['successful_endpoints']}\n";
    echo "Endpoints avec données: {$summary['endpoints_with_data']}\n";
    echo "Tests de paramètres: {$summary['total_parameter_tests']}\n";
    echo "Tests réussis: {$summary['successful_parameter_tests']}\n";
    echo "Tests avec données: {$summary['parameter_tests_with_data']}\n";
}

echo "\n🎯 CONCLUSION:\n";
if ($data['auth']['success'] ?? false) {
    echo "✅ L'API Eklektik est accessible et authentifiée\n";
    
    $hasWorkingEndpoints = ($data['summary']['successful_endpoints'] ?? 0) > 0;
    $hasWorkingParams = ($data['summary']['successful_parameter_tests'] ?? 0) > 0;
    
    if ($hasWorkingEndpoints || $hasWorkingParams) {
        echo "✅ Des endpoints fonctionnent et retournent des données\n";
        echo "📋 Les données affichées dans le dashboard sont RÉELLES (pas de mock)\n";
    } else {
        echo "⚠️  Aucun endpoint ne retourne de données\n";
        echo "📋 Les données affichées dans le dashboard sont des FALLBACK/MOCK\n";
    }
} else {
    echo "❌ L'API Eklektik n'est pas accessible\n";
    echo "📋 Les données affichées dans le dashboard sont des FALLBACK/MOCK\n";
}

echo "\n";

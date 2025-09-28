<?php

require_once 'vendor/autoload.php';

// Charger l'environnement Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Test sécurisé Club Privilèges Export API\n";
echo "==========================================\n\n";

use App\Services\CpIncrementalExport;

try {
    $cpExport = new CpIncrementalExport();
    
    echo "1. Test de connexion avec délai de sécurité...\n";
    echo "   (Attente de 30 secondes pour éviter le rate limiting)\n";
    sleep(30);
    
    $result = $cpExport->testConnection();
    
    if ($result['success']) {
        echo "   ✅ Connexion réussie!\n";
        echo "   📊 Réponse: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Connexion échouée: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n2. Test de synchronisation avec payload minimal...\n";
echo "   (Attente de 30 secondes supplémentaires)\n";
sleep(30);

try {
    $cpExport = new CpIncrementalExport();
    
    // Test avec un payload très simple
    $minimalPayload = [
        'tables' => [
            'client' => [
                'colonne_id_name' => 'client_id',
                'last_inserted_id' => 0
            ]
        ]
    ];
    
    echo "   📦 Payload: " . json_encode($minimalPayload, JSON_PRETTY_PRINT) . "\n";
    
    $response = \Illuminate\Support\Facades\Http::timeout(30)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => config('sync_export.token'),
        ])
        ->post(config('sync_export.endpoint'), $minimalPayload);
    
    echo "   - Status: " . $response->status() . "\n";
    echo "   - Headers: " . json_encode($response->headers()) . "\n";
    echo "   - Body: " . substr($response->body(), 0, 500) . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "   ✅ Requête réussie!\n";
        echo "   📊 Données: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "   ❌ Erreur HTTP: " . $response->status() . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n✅ Test sécurisé terminé.\n";

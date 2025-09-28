<?php

require_once 'vendor/autoload.php';

// Charger l'environnement Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Diagnostic Club Privilèges Export API\n";
echo "=====================================\n\n";

// 1. Vérifier la configuration
echo "1. Configuration:\n";
echo "   - Endpoint: " . config('sync_export.endpoint', 'NON CONFIGURÉ') . "\n";
echo "   - Token: " . (config('sync_export.token') ? 'CONFIGURÉ (' . strlen(config('sync_export.token')) . ' caractères)' : 'NON CONFIGURÉ') . "\n";
echo "   - Timeout: " . config('sync_export.timeout', 'NON CONFIGURÉ') . "\n";
echo "   - Retry attempts: " . config('sync_export.retry_attempts', 'NON CONFIGURÉ') . "\n\n";

// 2. Vérifier les variables d'environnement
echo "2. Variables d'environnement:\n";
echo "   - CP_EXPORT_URL: " . env('CP_EXPORT_URL', 'NON DÉFINIE') . "\n";
echo "   - CP_EXPORT_TOKEN: " . (env('CP_EXPORT_TOKEN') ? 'DÉFINIE (' . strlen(env('CP_EXPORT_TOKEN')) . ' caractères)' : 'NON DÉFINIE') . "\n";
echo "   - CP_EXPORT_TIMEOUT: " . env('CP_EXPORT_TIMEOUT', 'NON DÉFINIE') . "\n\n";

// 3. Test de connexion HTTP simple
echo "3. Test de connexion HTTP:\n";
try {
    $response = \Illuminate\Support\Facades\Http::timeout(10)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => config('sync_export.token', 'test-token'),
        ])
        ->post(config('sync_export.endpoint', 'https://clubprivileges.app/api/get-pending-sync-data'), [
            'tables' => [
                'client' => [
                    'colonne_id_name' => 'client_id',
                    'last_inserted_id' => 0
                ]
            ]
        ]);

    echo "   - Status: " . $response->status() . "\n";
    echo "   - Headers: " . json_encode($response->headers()) . "\n";
    echo "   - Body: " . substr($response->body(), 0, 500) . "\n";
    
    if ($response->successful()) {
        echo "   ✅ Connexion réussie!\n";
    } else {
        echo "   ❌ Erreur HTTP: " . $response->status() . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n4. Test avec Bearer token:\n";
try {
    $response = \Illuminate\Support\Facades\Http::timeout(10)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . config('sync_export.token', 'test-token'),
        ])
        ->post(config('sync_export.endpoint', 'https://clubprivileges.app/api/get-pending-sync-data'), [
            'tables' => [
                'client' => [
                    'colonne_id_name' => 'client_id',
                    'last_inserted_id' => 0
                ]
            ]
        ]);

    echo "   - Status: " . $response->status() . "\n";
    echo "   - Body: " . substr($response->body(), 0, 500) . "\n";
    
    if ($response->successful()) {
        echo "   ✅ Connexion avec Bearer réussie!\n";
    } else {
        echo "   ❌ Erreur HTTP avec Bearer: " . $response->status() . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception avec Bearer: " . $e->getMessage() . "\n";
}

echo "\n5. Vérification de la table sync_state:\n";
try {
    $count = \Illuminate\Support\Facades\DB::table('sync_state')->count();
    echo "   - Nombre d'entrées: " . $count . "\n";
    
    $tables = \Illuminate\Support\Facades\DB::table('sync_state')->pluck('table_name')->toArray();
    echo "   - Tables configurées: " . implode(', ', $tables) . "\n";
} catch (Exception $e) {
    echo "   ❌ Erreur base de données: " . $e->getMessage() . "\n";
}

echo "\n✅ Diagnostic terminé.\n";

<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

echo "🔐 Test d'authentification Eklektik...\n";

$response = Http::timeout(30)
    ->asForm()
    ->post('https://payment.eklectic.tn/API/oauth/token', [
        'client_id' => '0a2e605d-88f6-11ec-9feb-fa163e3dd8b3',
        'client_secret' => 'ee60bb148a0e468a5053f9db41008780',
        'grant_type' => 'client_credentials'
    ]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";

if ($response->successful()) {
    $data = $response->json();
    echo "Token: " . ($data['access_token'] ?? 'NON TROUVÉ') . "\n";
    
    // Test avec le token
    if (isset($data['access_token'])) {
        echo "\n📡 Test de récupération des abonnés...\n";
        
        $subscribersResponse = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $data['access_token'],
                'Accept' => 'application/json'
            ])
            ->get('https://payment.eklectic.tn/API/subscription/subscribers');
            
        echo "Subscribers Status: " . $subscribersResponse->status() . "\n";
        echo "Subscribers Body: " . $subscribersResponse->body() . "\n";
    }
} else {
    echo "❌ Erreur d'authentification\n";
}

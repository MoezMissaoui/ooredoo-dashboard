<?php

echo "🔍 Test de disponibilité API Club Privilèges\n";
echo "==========================================\n\n";

$url = 'https://clubprivileges.app/api/get-pending-sync-data';
$token = 'cp_dashboard_aBcDe8584FgHiJkLmj854KNoPqRsTuVwXyZ01234ythrdGHjs56789';

// Test de base avec une requête simple
echo "1. Test de disponibilité de base:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'tables' => [
        'client' => [
            'colonne_id_name' => 'client_id',
            'last_inserted_id' => 0
        ]
    ]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "   - HTTP Code: $httpCode\n";
echo "   - Response: " . substr($response, 0, 200) . "\n";
if ($error) {
    echo "   - Error: $error\n";
}

// Interprétation des codes d'erreur
echo "\n2. Interprétation:\n";
switch ($httpCode) {
    case 200:
        echo "   ✅ API disponible et fonctionnelle\n";
        break;
    case 500:
        echo "   ⚠️ Erreur serveur (500) - Problème de mémoire côté serveur\n";
        echo "   💡 Solution: Utiliser des requêtes plus petites\n";
        break;
    case 503:
        echo "   ⚠️ Service indisponible (503) - Serveur en maintenance ou surchargé\n";
        echo "   💡 Solution: Réessayer plus tard\n";
        break;
    case 429:
        echo "   ⚠️ Trop de requêtes (429) - Rate limiting activé\n";
        echo "   💡 Solution: Attendre et réduire la fréquence\n";
        break;
    case 401:
        echo "   ❌ Non autorisé (401) - Token invalide\n";
        echo "   💡 Solution: Vérifier le token\n";
        break;
    case 403:
        echo "   ❌ Interdit (403) - Accès refusé\n";
        echo "   💡 Solution: Vérifier les permissions\n";
        break;
    default:
        echo "   ❓ Code inconnu: $httpCode\n";
}

echo "\n3. Recommandations:\n";
if ($httpCode === 503) {
    echo "   - L'API Club Privilèges est temporairement indisponible\n";
    echo "   - Attendez quelques minutes et réessayez\n";
    echo "   - Configurez le système pour retry automatique\n";
} elseif ($httpCode === 500) {
    echo "   - L'API a des problèmes de mémoire\n";
    echo "   - Utilisez des requêtes plus petites (une table à la fois)\n";
    echo "   - Augmentez les délais entre les requêtes\n";
} elseif ($httpCode === 429) {
    echo "   - Rate limiting activé\n";
    echo "   - Augmentez les délais entre les requêtes\n";
    echo "   - Réduisez la fréquence de synchronisation\n";
} else {
    echo "   - Vérifiez la configuration du token\n";
    echo "   - Contactez l'équipe Club Privilèges si le problème persiste\n";
}

echo "\n✅ Test de disponibilité terminé.\n";

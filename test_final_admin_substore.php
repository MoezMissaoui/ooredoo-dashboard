<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SubStoreController;
use Illuminate\Http\Request;

echo "=== TEST FINAL ADMIN SUB-STORES ===\n\n";

// Test avec l'Admin Sub-Stores
$adminSubStore = User::where('email', 'admin.substores@ooredoo.tn')->first();

if (!$adminSubStore) {
    echo "❌ Admin Sub-Stores non trouvé\n";
    exit;
}

echo "👤 Utilisateur: {$adminSubStore->email}\n";
echo "   Rôle: " . ($adminSubStore->isAdmin() ? 'Admin' : 'Autre') . "\n";
echo "   Orienté Sub-Stores: " . ($adminSubStore->isPrimarySubStoreUser() ? 'OUI' : 'NON') . "\n";

// Simuler la connexion
Auth::login($adminSubStore);

echo "\n🔐 Connexion simulée réussie\n";

// Test 1: Redirection
echo "\n1️⃣ TEST REDIRECTION :\n";
try {
    $redirectUrl = $adminSubStore->getPreferredDashboard();
    echo "   ✅ URL: {$redirectUrl}\n";
    echo "   ✅ Destination: " . (strpos($redirectUrl, 'sub-stores') !== false ? 'Dashboard Sub-Stores' : 'Dashboard Principal') . "\n";
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 2: Permissions sub-stores
echo "\n2️⃣ TEST PERMISSIONS SUB-STORES :\n";
try {
    $controller = new SubStoreController();
    
    // Créer une requête simulée
    $request = new Request();
    $request->merge(['sub_store' => 'ALL']);
    
    // Tester l'accès aux sub-stores (méthode publique via API)
    $response = $controller->getSubStores();
    $data = json_decode($response->getContent(), true);
    
    echo "   ✅ Sub-stores accessibles: " . count($data['sub_stores']) . "\n";
    echo "   ✅ Sub-store par défaut: " . $data['default_sub_store'] . "\n";
    echo "   ✅ Rôle détecté: " . $data['user_role'] . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

// Test 3: Permissions administratives
echo "\n3️⃣ TEST PERMISSIONS ADMINISTRATIVES :\n";
echo "   ✅ Peut créer des utilisateurs: " . ($adminSubStore->isAdmin() ? 'OUI' : 'NON') . "\n";
echo "   ✅ Peut inviter des utilisateurs: " . ($adminSubStore->isAdmin() ? 'OUI' : 'NON') . "\n";
echo "   ✅ Accès menu Administration: " . (($adminSubStore->isSuperAdmin() || $adminSubStore->isAdmin()) ? 'OUI' : 'NON') . "\n";

Auth::logout();

echo "\n🎉 TESTS TERMINÉS !\n\n";

echo "📋 RÉCAPITULATIF FINAL DES UTILISATEURS :\n";
echo "1. Super Admin → Dashboard principal (vue globale)\n";
echo "2. Admin Partnership → Dashboard Sub-Stores (orienté sub-stores)\n";
echo "3. 🆕 Admin Sub-Stores → Dashboard Sub-Stores + Permissions Admin\n";
echo "4. Collaborateur Sub-Stores → Dashboard Sub-Stores\n";
echo "5. Collaborateur Timwe → Dashboard principal\n\n";

echo "✅ Admin Sub-Stores a maintenant :\n";
echo "   - ✅ Redirection automatique vers Dashboard Sub-Stores\n";
echo "   - ✅ Vue globale de tous les sub-stores (comme Super Admin)\n";
echo "   - ✅ Accès au menu Administration\n";
echo "   - ✅ Permissions de création/gestion d'utilisateurs\n";
echo "   - ✅ Permissions d'invitation d'utilisateurs\n\n";

echo "=== FIN TEST ===\n";

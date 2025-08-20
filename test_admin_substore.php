<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "=== TEST ADMIN SUB-STORES REDIRECTION ===\n\n";

$testUsers = [
    'superadmin@ooredoo.tn' => 'Super Admin',
    'admin.partnership@ooredoo.tn' => 'Admin Partnership', 
    'admin.substores@ooredoo.tn' => '🆕 Admin Sub-Stores',
    'collaborateur.substores@ooredoo.tn' => 'Collaborateur Sub-Stores',
    'collaborateur.timwe@ooredoo.tn' => 'Collaborateur Timwe'
];

foreach ($testUsers as $email => $label) {
    $user = User::where('email', $email)->first();
    
    if ($user) {
        echo "👤 {$label} ({$email})\n";
        
        // Déterminer le rôle
        if ($user->isSuperAdmin()) {
            $roleString = "Super Admin";
        } elseif ($user->isAdmin()) {
            $roleString = "Admin";
        } elseif ($user->isCollaborator()) {
            $roleString = "Collaborator";
        } else {
            $roleString = "Inconnu";
        }
        
        echo "   Rôle: {$roleString}\n";
        
        $primaryOperator = $user->primaryOperator();
        if ($primaryOperator) {
            echo "   Opérateur principal: {$primaryOperator->operator_name}\n";
        } else {
            echo "   Opérateur principal: Aucun (Super Admin)\n";
        }
        
        echo "   Est orienté sub-stores: " . ($user->isPrimarySubStoreUser() ? 'OUI' : 'NON') . "\n";
        
        // Simuler la connexion pour tester l'URL réelle
        Auth::login($user);
        
        try {
            $redirectUrl = $user->getPreferredDashboard();
            echo "   🔗 URL de redirection: {$redirectUrl}\n";
            
            if (strpos($redirectUrl, 'sub-stores') !== false) {
                echo "   📊 Destination: 🏪 Dashboard Sub-Stores\n";
            } else {
                echo "   📊 Destination: 📈 Dashboard Principal\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
        }
        
        Auth::logout();
        echo "\n";
    } else {
        echo "❌ Utilisateur non trouvé: {$email}\n\n";
    }
}

echo "=== FIN TEST ===\n";

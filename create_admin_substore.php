<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\UserOperator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== CRÉATION ADMIN SUB-STORES ===\n";

try {
    DB::beginTransaction();

    // Récupérer le rôle admin
    $adminRole = Role::where('name', 'admin')->first();
    
    if (!$adminRole) {
        throw new Exception('Rôle admin non trouvé');
    }

    // Créer l'utilisateur Admin Sub-Stores
    $adminSubStore = User::create([
        'name' => 'Sophie Admin Sub-Stores',
        'first_name' => 'Sophie',
        'last_name' => 'Admin',
        'email' => 'admin.substores@ooredoo.tn',
        'password' => Hash::make('password123'),
        'role_id' => $adminRole->id,
        'status' => 'active',
        'is_otp_enabled' => false,
        'email_verified_at' => now(),
        'created_by' => 1 // Super Admin
    ]);

    echo "✅ Admin Sub-Stores créé: {$adminSubStore->email}\n";

    // Assigner l'opérateur "Sub-Stores"
    UserOperator::create([
        'user_id' => $adminSubStore->id,
        'operator_name' => 'Sub-Stores',
        'is_primary' => true,
        'assigned_by' => 1
    ]);

    echo "✅ Opérateur 'Sub-Stores' assigné\n";

    DB::commit();

    echo "\n🎉 ADMIN SUB-STORES CRÉÉ AVEC SUCCÈS !\n\n";
    
    echo "📋 NOUVEAU COMPTE :\n";
    echo "Admin Sub-Stores : admin.substores@ooredoo.tn / password123\n";
    echo "→ Redirection : Dashboard Sub-Stores (avec permissions admin)\n\n";
    
    echo "📊 RÉCAPITULATIF COMPLET :\n";
    echo "1. Super Admin : superadmin@ooredoo.tn → Dashboard principal (vue globale)\n";
    echo "2. Admin Partnership : admin.partnership@ooredoo.tn → Dashboard principal (filtrée)\n";
    echo "3. 🆕 Admin Sub-Stores : admin.substores@ooredoo.tn → Dashboard Sub-Stores\n";
    echo "4. Collaborateur Sub-Stores : collaborateur.substores@ooredoo.tn → Dashboard Sub-Stores\n";
    echo "5. Collaborateur Timwe : collaborateur.timwe@ooredoo.tn → Dashboard principal (filtrée)\n\n";

} catch (Exception $e) {
    DB::rollback();
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "=== FIN CRÉATION ===\n";

<?php

require_once 'vendor/autoload.php';

// Charger l'environnement Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Vérification des tables Eklektik\n";
echo "==================================\n\n";

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    // 1. Vérifier les tables Eklektik
    echo "1. Tables Eklektik existantes:\n";
    $tables = DB::select("SHOW TABLES LIKE '%eklektik%'");
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        echo "   ✅ {$tableName}\n";
    }
    echo "\n";

    // 2. Vérifier la structure de notifications_tracking
    if (Schema::hasTable('eklektik_notifications_tracking')) {
        echo "2. Structure de eklektik_notifications_tracking:\n";
        $columns = DB::select("DESCRIBE eklektik_notifications_tracking");
        foreach ($columns as $column) {
            echo "   - {$column->Field} ({$column->Type})";
            if ($column->Key === 'PRI') echo " [PRIMARY KEY]";
            if ($column->Key === 'MUL') echo " [INDEX]";
            if ($column->Null === 'NO') echo " [NOT NULL]";
            echo "\n";
        }
        echo "\n";
    } else {
        echo "   ❌ Table eklektik_notifications_tracking manquante\n\n";
    }

    // 3. Vérifier la structure de transactions_tracking
    if (Schema::hasTable('eklektik_transactions_tracking')) {
        echo "3. Structure de eklektik_transactions_tracking:\n";
        $columns = DB::select("DESCRIBE eklektik_transactions_tracking");
        foreach ($columns as $column) {
            echo "   - {$column->Field} ({$column->Type})";
            if ($column->Key === 'PRI') echo " [PRIMARY KEY]";
            if ($column->Key === 'MUL') echo " [INDEX]";
            if ($column->Null === 'NO') echo " [NOT NULL]";
            echo "\n";
        }
        echo "\n";
    } else {
        echo "   ❌ Table eklektik_transactions_tracking manquante\n\n";
    }

    // 4. Vérifier les migrations
    echo "4. Migrations Eklektik:\n";
    $migrations = DB::table('migrations')
        ->where('migration', 'like', '%eklektik%')
        ->orderBy('id')
        ->get();
    
    foreach ($migrations as $migration) {
        echo "   ✅ {$migration->migration} (Batch: {$migration->batch})\n";
    }
    echo "\n";

    // 5. Vérifier les index
    echo "5. Index sur eklektik_notifications_tracking:\n";
    $indexes = DB::select("SHOW INDEX FROM eklektik_notifications_tracking");
    foreach ($indexes as $index) {
        echo "   - {$index->Key_name} ({$index->Index_type}) sur {$index->Column_name}\n";
    }
    echo "\n";

    // 6. Test de création d'enregistrement
    echo "6. Test de création d'enregistrement:\n";
    try {
        $testId = DB::table('eklektik_notifications_tracking')->insertGetId([
            'notification_id' => 999999,
            'kpi_updated' => false,
            'processing_batch_id' => 'test_' . time(),
            'processing_metadata' => json_encode(['test' => true]),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "   ✅ Enregistrement de test créé avec ID: {$testId}\n";
        
        // Supprimer l'enregistrement de test
        DB::table('eklektik_notifications_tracking')->where('id', $testId)->delete();
        echo "   ✅ Enregistrement de test supprimé\n";
    } catch (Exception $e) {
        echo "   ❌ Erreur lors du test: " . $e->getMessage() . "\n";
    }

    echo "\n✅ Vérification terminée avec succès!\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de la vérification: " . $e->getMessage() . "\n";
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EklektikRevenueSharingService;

class TestRevenueSharing extends Command
{
    protected $signature = 'eklektik:test-revenue-sharing';
    protected $description = 'Tester les formules de calcul du partage des revenus Eklektik';

    public function handle()
    {
        $this->info('🧮 Test des Formules de Partage des Revenus Eklektik');
        $this->info('==================================================');
        $this->newLine();

        $service = new EklektikRevenueSharingService();

        // Test des formules
        $this->info('1️⃣ Validation des formules de calcul...');
        $validationResults = $service->validateFormulas();
        
        foreach ($validationResults as $result) {
            $status = $result['is_correct'] ? '✅' : '❌';
            $this->info("$status {$result['operator']}:");
            $this->info("  TTC: {$result['ttc']} TND");
            $this->info("  HT Calculé: " . number_format($result['calculated_ht'], 2) . " TND");
            $this->info("  HT Attendu: " . number_format($result['expected_ht'], 2) . " TND");
            $this->info("  Différence: " . number_format($result['difference'], 4) . " TND");
            $this->newLine();
        }

        // Test avec des données réelles
        $this->info('2️⃣ Test avec des données réelles...');
        $testData = [
            ['operator' => 'Orange', 'ttc' => 1000.00],
            ['operator' => 'Taraji', 'ttc' => 500.00],
            ['operator' => 'TT', 'ttc' => 2000.00],
        ];

        foreach ($testData as $data) {
            $sharing = $service->calculateRevenueSharing($data['operator'], $data['ttc']);
            
            $this->info("📊 {$data['operator']} (TTC: {$data['ttc']} TND):");
            $this->info("  Montant HT: " . number_format($sharing['montant_total_ht'], 2) . " TND");
            $this->info("  Part Opérateur: {$sharing['part_operateur']}% = " . number_format($sharing['ca_operateur'], 2) . " TND");
            $this->info("  Part Agrégateur: {$sharing['part_agregateur']}% = " . number_format($sharing['ca_agregateur'], 2) . " TND");
            $this->info("  Part BigDeal: {$sharing['part_bigdeal']}% = " . number_format($sharing['ca_bigdeal'], 2) . " TND");
            
            // Vérifier que la somme des parts = 100%
            $totalParts = $sharing['part_operateur'] + $sharing['part_agregateur'] + $sharing['part_bigdeal'];
            $this->info("  Total des parts: {$totalParts}%");
            
            // Vérifier que la somme des CA = Montant HT
            $totalCA = $sharing['ca_operateur'] + $sharing['ca_agregateur'] + $sharing['ca_bigdeal'];
            $difference = abs($totalCA - $sharing['montant_total_ht']);
            $this->info("  Vérification CA: " . number_format($totalCA, 2) . " TND (diff: " . number_format($difference, 4) . ")");
            $this->newLine();
        }

        $this->info('✅ Test terminé!');
    }
}


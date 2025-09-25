<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EklektikAlertService;

class TestEklektikAlerts extends Command
{
    protected $signature = 'eklektik:test-alerts';
    protected $description = 'Tester le système d\'alertes Eklektik';

    public function handle()
    {
        $this->info('🚨 Test du Système d\'Alertes Eklektik');
        $this->info('=====================================');
        $this->newLine();

        $alertService = new EklektikAlertService();

        // Test 1: Statut de santé du système
        $this->info('1️⃣ Test du statut de santé du système...');
        $health = $alertService->getSystemHealth();
        
        $statusColor = match($health['status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white'
        };
        
        $this->info("  Statut: <fg={$statusColor}>{$health['status']}</>");
        $this->info("  Dernière sync: " . ($health['last_sync'] ?: 'Jamais'));
        $this->info("  Heures depuis sync: " . ($health['hours_since_sync'] ?: 'N/A'));
        $this->info("  Total enregistrements: " . $health['total_records']);
        $this->newLine();

        // Test 2: Statut par opérateur
        $this->info('2️⃣ Test du statut par opérateur...');
        foreach ($health['operators_status'] as $operator => $status) {
            $hasData = $status['has_data'] ? '✅' : '❌';
            $this->info("  $operator: $hasData " . ($status['has_data'] ? 'Données disponibles' : 'Aucune donnée'));
            if ($status['has_data']) {
                $this->info("    - Dernière sync: " . ($status['last_sync'] ?: 'N/A'));
                $this->info("    - Enregistrements: " . $status['records_count']);
                $this->info("    - CA BigDeal: " . number_format($status['total_ca_bigdeal'], 2) . " TND");
            }
        }
        $this->newLine();

        // Test 3: Alertes
        $this->info('3️⃣ Test des alertes...');
        $alerts = $health['alerts'];
        
        if (empty($alerts)) {
            $this->info('  ✅ Aucune alerte détectée');
        } else {
            $this->info("  🚨 " . count($alerts) . " alertes détectées:");
            foreach ($alerts as $alert) {
                $typeColor = match($alert['type']) {
                    'critical' => 'red',
                    'warning' => 'yellow',
                    'info' => 'blue',
                    default => 'white'
                };
                
                $this->info("    - <fg={$typeColor}>[{$alert['type']}]</> {$alert['message']}");
                if (isset($alert['action'])) {
                    $this->info("      Action: {$alert['action']}");
                }
                if (isset($alert['details'])) {
                    foreach ($alert['details'] as $detail) {
                        $this->info("      Détail: $detail");
                    }
                }
            }
        }
        $this->newLine();

        // Test 4: Rapport de santé
        $this->info('4️⃣ Test du rapport de santé...');
        $report = $alertService->generateHealthReport();
        
        $this->info("  Timestamp: {$report['timestamp']}");
        $this->info("  Statut: {$report['status']}");
        $this->info("  Opérateurs avec données: {$report['summary']['operators_with_data']}/3");
        $this->newLine();

        // Test 5: Recommandations
        $this->info('5️⃣ Recommandations:');
        foreach ($report['recommendations'] as $recommendation) {
            $this->info("  💡 $recommendation");
        }
        $this->newLine();

        // Test 6: Traitement des alertes
        $this->info('6️⃣ Test du traitement des alertes...');
        $result = $alertService->processAlerts();
        
        $this->info("  Total alertes: {$result['total_alerts']}");
        $this->info("  Alertes traitées: {$result['processed']}");
        $this->newLine();

        $this->info('✅ Test terminé!');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EklektikStatsService;

class TestEklektikStatsApi extends Command
{
    protected $signature = 'eklektik:test-stats-api';
    protected $description = 'Test de l\'API des statistiques Eklektik locales';

    public function handle()
    {
        $this->info('🧪 Test de l\'API des Statistiques Eklektik Locales');
        $this->info('==================================================');
        $this->newLine();

        $service = new EklektikStatsService();

        // Test 1: Récupérer les statistiques des 7 derniers jours
        $this->info('1️⃣ Test des statistiques des 7 derniers jours...');
        
        $startDate = now()->subDays(7)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        
        $stats = $service->getLocalStats($startDate, $endDate);
        $kpis = $service->calculateKPIs($stats);
        
        $this->info("Période: $startDate à $endDate");
        $this->info("Nombre d'enregistrements: " . $stats->count());
        $this->info("KPIs calculés:");
        $this->info("  - Nouveaux abonnements: " . $kpis['total_new_subscriptions']);
        $this->info("  - Renouvellements: " . $kpis['total_renewals']);
        $this->info("  - Facturations: " . $kpis['total_charges']);
        $this->info("  - Désabonnements: " . $kpis['total_unsubscriptions']);
        $this->info("  - Revenus totaux: " . number_format($kpis['total_revenue'], 2) . " TND");
        $this->info("  - Taux de facturation moyen: " . number_format($kpis['average_billing_rate'], 2) . "%");
        $this->info("  - Abonnés actifs: " . $kpis['total_active_subscribers']);
        $this->newLine();

        // Test 2: Répartition par opérateur
        $this->info('2️⃣ Test de la répartition par opérateur...');
        
        if (!empty($kpis['operators_distribution'])) {
            foreach ($kpis['operators_distribution'] as $operator => $data) {
                $this->info("  $operator:");
                $this->info("    - Total: {$data['total']} jours");
                $this->info("    - Nouveaux: {$data['new_subscriptions']}");
                $this->info("    - Renouvellements: {$data['renewals']}");
                $this->info("    - Facturations: {$data['charges']}");
                $this->info("    - Revenus: " . number_format($data['revenue'], 2) . " TND");
            }
        } else {
            $this->warn('  Aucune donnée de répartition par opérateur');
        }
        $this->newLine();

        // Test 3: Afficher un échantillon des données
        $this->info('3️⃣ Échantillon des données:');
        
        if ($stats->isNotEmpty()) {
            $headers = ['Date', 'Opérateur', 'Nouveaux', 'Renouvellements', 'Facturations', 'Revenus (TND)'];
            $rows = $stats->take(5)->map(function ($stat) {
                return [
                    $stat->date,
                    $stat->operator,
                    $stat->new_subscriptions,
                    $stat->renewals,
                    $stat->charges,
                    number_format($stat->total_revenue, 2)
                ];
            })->toArray();
            
            $this->table($headers, $rows);
        } else {
            $this->warn('  Aucune donnée disponible');
        }

        $this->newLine();
        $this->info('✅ Test terminé!');
    }
}
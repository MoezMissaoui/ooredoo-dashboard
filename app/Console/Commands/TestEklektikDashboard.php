<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EklektikCacheService;

class TestEklektikDashboard extends Command
{
    protected $signature = 'eklektik:test-dashboard';
    protected $description = 'Tester les APIs du dashboard Eklektik';

    public function handle()
    {
        $this->info('🧪 Test des APIs du Dashboard Eklektik');
        $this->info('====================================');
        $this->newLine();

        $cacheService = new EklektikCacheService();

        // Test 1: KPIs
        $this->info('1️⃣ Test des KPIs...');
        $kpis = $cacheService->getCachedKPIs('2025-09-20', '2025-09-22');
        
        $this->info("  - Nouveaux abonnements: " . $kpis['total_new_subscriptions']);
        $this->info("  - Désabonnements: " . $kpis['total_unsubscriptions']);
        $this->info("  - Simchurn: " . $kpis['total_simchurn']);
        $this->info("  - Facturations: " . $kpis['total_facturation']);
        $this->info("  - Revenus TTC: " . number_format($kpis['total_revenue_ttc'], 2) . " TND");
        $this->info("  - Revenus HT: " . number_format($kpis['total_revenue_ht'], 2) . " TND");
        $this->info("  - CA BigDeal: " . number_format($kpis['total_ca_bigdeal'], 2) . " TND");
        $this->info("  - CA Opérateurs: " . number_format($kpis['total_ca_operateur'], 2) . " TND");
        $this->info("  - CA Agrégateur: " . number_format($kpis['total_ca_agregateur'], 2) . " TND");
        $this->newLine();

        // Test 2: Revenus BigDeal
        $this->info('2️⃣ Test des revenus BigDeal...');
        $bigDealRevenue = $cacheService->getCachedBigDealRevenue('2025-09-20', '2025-09-22');
        
        $this->info("  - Total CA BigDeal: " . number_format($bigDealRevenue['total_ca_bigdeal'], 2) . " TND");
        $this->info("  - Total Revenus HT: " . number_format($bigDealRevenue['total_revenue_ht'], 2) . " TND");
        $this->info("  - Pourcentage BigDeal: " . number_format($bigDealRevenue['bigdeal_percentage'], 2) . "%");
        $this->newLine();

        // Test 3: Répartition par opérateur
        $this->info('3️⃣ Test de la répartition par opérateur...');
        $distribution = $cacheService->getCachedOperatorsDistribution('2025-09-20', '2025-09-22');
        
        foreach ($distribution as $operator => $data) {
            $this->info("  📊 $operator:");
            $this->info("    - Enregistrements: " . $data['total_records']);
            $this->info("    - Revenus TTC: " . number_format($data['revenue_ttc'], 2) . " TND");
            $this->info("    - Revenus HT: " . number_format($data['revenue_ht'], 2) . " TND");
            $this->info("    - CA BigDeal: " . number_format($data['ca_bigdeal'], 2) . " TND");
            $this->info("    - CA Opérateur: " . number_format($data['ca_operateur'], 2) . " TND");
            $this->info("    - CA Agrégateur: " . number_format($data['ca_agregateur'], 2) . " TND");
            $this->info("    - Offres: " . $data['offers']->count());
        }
        $this->newLine();

        // Test 4: Statistiques détaillées
        $this->info('4️⃣ Test des statistiques détaillées...');
        $detailedStats = $cacheService->getCachedDetailedStats('2025-09-20', '2025-09-22');
        
        $this->info("  - Nombre de jours: " . $detailedStats->count());
        $this->info("  - Total Revenus TTC: " . number_format($detailedStats->sum('total_revenue_ttc'), 2) . " TND");
        $this->info("  - Total Revenus HT: " . number_format($detailedStats->sum('total_revenue_ht'), 2) . " TND");
        $this->info("  - Total CA BigDeal: " . number_format($detailedStats->sum('total_ca_bigdeal'), 2) . " TND");
        $this->newLine();

        // Test 5: Cache stats
        $this->info('5️⃣ Test des statistiques de cache...');
        $cacheStats = $cacheService->getCacheStats();
        
        $this->info("  - Nombre de clés en cache: " . count($cacheStats));
        foreach ($cacheStats as $stat) {
            $this->info("    - " . $stat['key'] . " (TTL: " . $stat['ttl'] . "s, " . $stat['expires_in'] . ")");
        }
        $this->newLine();

        $this->info('✅ Test terminé!');
    }
}


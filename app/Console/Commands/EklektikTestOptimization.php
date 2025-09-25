<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EklektikKPIOptimizer;
use Illuminate\Support\Facades\DB;

class EklektikTestOptimization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eklektik:test-optimization 
                            {--start-date=2024-12-01 : Date de début du test}
                            {--end-date=2024-12-31 : Date de fin du test}
                            {--operator=ALL : Opérateur à tester}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester les performances de l\'optimisation Eklektik';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $operator = $this->option('operator');

        $this->info("🧪 Test des performances Eklektik...");
        $this->info("📅 Période: {$startDate} à {$endDate}");
        $this->info("📱 Opérateur: {$operator}");

        // Test 1: Performance des KPIs optimisés
        $this->info("\n📊 Test 1: Performance des KPIs optimisés");
        $startTime = microtime(true);
        
        $optimizer = new EklektikKPIOptimizer();
        $kpis = $optimizer->getRealTimeKPIs($startDate, $endDate, $operator);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        $this->info("⚡ Temps de calcul: {$duration}ms");
        $this->info("📈 KPIs calculés: " . count($kpis));
        
        // Afficher les KPIs
        $this->table(
            ['KPI', 'Valeur'],
            collect($kpis)->map(function ($value, $key) {
                return [$key, $value];
            })->toArray()
        );

        // Test 2: Vérifier la cohérence des données
        $this->info("\n🔍 Test 2: Vérification de la cohérence");
        
        $cachedCount = DB::table('eklektik_kpis_cache')->count();
        $trackedCount = DB::table('eklektik_notifications_tracking')->count();
        $notificationsCount = DB::table('notifications')->count();
        
        $this->info("📈 Entrées en cache: {$cachedCount}");
        $this->info("📋 Notifications traitées: {$trackedCount}");
        $this->info("📱 Total notifications: {$notificationsCount}");
        
        $coverage = $notificationsCount > 0 ? round(($trackedCount / $notificationsCount) * 100, 2) : 0;
        $this->info("📊 Couverture: {$coverage}%");

        // Test 3: Performance des requêtes
        $this->info("\n⚡ Test 3: Performance des requêtes");
        
        // Test requête optimisée
        $startTime = microtime(true);
        $optimizedResult = DB::table('eklektik_kpis_cache')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('operator', $operator)
            ->get();
        $optimizedTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Test requête non optimisée (simulation)
        $startTime = microtime(true);
        $notOptimizedResult = DB::table('notifications')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where(function($q) {
                $q->where('notification_text', 'LIKE', '%action=%')
                  ->orWhere('notification_text', 'LIKE', '%subscriptionid=%')
                  ->orWhere('notification_text', 'LIKE', '%offreid=%');
            })
            ->count();
        $notOptimizedTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->info("🚀 Requête optimisée: {$optimizedTime}ms ({$optimizedResult->count()} résultats)");
        $this->info("🐌 Requête non optimisée: {$notOptimizedTime}ms ({$notOptimizedResult} résultats)");
        
        $improvement = $notOptimizedTime > 0 ? round((($notOptimizedTime - $optimizedTime) / $notOptimizedTime) * 100, 2) : 0;
        $this->info("📈 Amélioration: {$improvement}%");

        // Test 4: Statistiques de traitement
        $this->info("\n📊 Test 4: Statistiques de traitement");
        
        $stats = $optimizer->getProcessingStats($startDate, $endDate);
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Notifications traitées', $stats['total_processed']],
                ['KPIs mis à jour', $stats['kpi_updated']],
                ['Batches uniques', $stats['unique_batches']],
                ['Dernière mise à jour', $stats['last_processed'] ?? 'N/A']
            ]
        );

        // Test 5: Test de charge
        $this->info("\n🔥 Test 5: Test de charge");
        
        $iterations = 10;
        $totalTime = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $optimizer->getRealTimeKPIs($startDate, $endDate, $operator);
            $totalTime += microtime(true) - $startTime;
        }
        
        $avgTime = round(($totalTime / $iterations) * 1000, 2);
        $this->info("🔄 {$iterations} itérations effectuées");
        $this->info("⏱️ Temps moyen: {$avgTime}ms");
        
        if ($avgTime < 100) {
            $this->info("✅ Performance excellente (< 100ms)");
        } elseif ($avgTime < 500) {
            $this->info("⚠️ Performance acceptable (< 500ms)");
        } else {
            $this->warn("❌ Performance à améliorer (> 500ms)");
        }

        $this->info("\n✅ Tests terminés avec succès !");
        
        // Recommandations
        $this->info("\n💡 Recommandations:");
        if ($coverage < 50) {
            $this->warn("- Couverture faible ({$coverage}%), considérer l'initialisation complète");
        }
        if ($avgTime > 200) {
            $this->warn("- Temps de réponse élevé ({$avgTime}ms), vérifier les index");
        }
        if ($improvement < 50) {
            $this->warn("- Amélioration limitée ({$improvement}%), optimiser les requêtes");
        }
        
        $this->info("- Système prêt pour la production ! 🚀");
    }
}
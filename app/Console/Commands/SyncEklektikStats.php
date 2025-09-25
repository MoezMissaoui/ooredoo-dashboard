<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EklektikStatsService;
use Carbon\Carbon;

class SyncEklektikStats extends Command
{
    protected $signature = 'eklektik:sync-stats 
                            {--period=30 : Nombre de jours à synchroniser}
                            {--start-date= : Date de début (YYYY-MM-DD)}
                            {--end-date= : Date de fin (YYYY-MM-DD)}
                            {--operator= : Opérateur spécifique (TT, Orange, Taraji)}
                            {--force : Forcer la synchronisation même si les données existent}';

    protected $description = 'Synchroniser les statistiques Eklektik depuis l\'API';

    public function handle()
    {
        $this->info('🔄 Synchronisation des statistiques Eklektik');
        $this->info('==========================================');
        $this->newLine();

        $service = new EklektikStatsService();

        // Déterminer la période de synchronisation
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $period = (int) $this->option('period');
        $operator = $this->option('operator');

        if (!$startDate || !$endDate) {
            if ($period === 1) {
                // Synchroniser seulement hier
                $startDate = $endDate = Carbon::yesterday()->format('Y-m-d');
                $this->info("📅 Synchronisation d'hier: $startDate");
            } else {
                // Synchroniser les X derniers jours
                $endDate = Carbon::yesterday()->format('Y-m-d');
                $startDate = Carbon::yesterday()->subDays($period - 1)->format('Y-m-d');
                $this->info("📅 Synchronisation des $period derniers jours: $startDate à $endDate");
            }
        } else {
            $this->info("📅 Synchronisation de la période: $startDate à $endDate");
        }

        if ($operator) {
            $this->info("🎯 Opérateur spécifique: $operator");
        }

        $this->newLine();

        // Vérifier si la synchronisation est activée
        if (!config('eklektik.stats.sync.enabled', true)) {
            $this->warn('⚠️ La synchronisation Eklektik est désactivée dans la configuration');
            return;
        }

        // Afficher les statistiques existantes
        $this->info('📊 Vérification des données existantes...');
        $existingStats = $service->getLocalStats($startDate, $endDate, $operator);
        $this->info("Données existantes: " . $existingStats->count() . " enregistrements");

        if ($existingStats->isNotEmpty() && !$this->option('force')) {
            if (!$this->confirm('Des données existent déjà pour cette période. Continuer ?')) {
                $this->info('❌ Synchronisation annulée');
                return;
            }
        }

        // Lancer la synchronisation
        $this->info('🚀 Début de la synchronisation...');
        $startTime = microtime(true);

        try {
            $results = $service->syncStatsForPeriod($startDate, $endDate);
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            // Afficher les résultats
            $this->newLine();
            $this->info('✅ Synchronisation terminée!');
            $this->info("⏱️ Durée: {$duration}s");
            $this->info("📊 Total synchronisé: {$results['total_synced']} enregistrements");
            $this->newLine();

            // Détails par opérateur
            $this->info('📈 Détails par opérateur:');
            foreach ($results['operators'] as $operatorName => $stats) {
                $this->info("  $operatorName: {$stats['synced']} enregistrements ({$stats['records']} récupérés)");
            }

            // Erreurs
            if (!empty($results['errors'])) {
                $this->newLine();
                $this->warn('⚠️ Erreurs rencontrées:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - $error");
                }
            }

            // Afficher un échantillon des données
            $this->newLine();
            $this->info('📋 Échantillon des données synchronisées:');
            $sampleStats = $service->getLocalStats($startDate, $endDate, $operator)->take(5);
            
            if ($sampleStats->isNotEmpty()) {
                $headers = ['Date', 'Opérateur', 'Nouveaux', 'Renouvellements', 'Facturations', 'Revenus (TND)'];
                $rows = $sampleStats->map(function ($stat) {
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
            }

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la synchronisation: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
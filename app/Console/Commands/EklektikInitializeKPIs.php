<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EklektikKPIOptimizer;

class EklektikInitializeKPIs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eklektik:initialize-kpis 
                            {--start-date=2021-01-01 : Date de début}
                            {--end-date= : Date de fin (défaut: aujourd\'hui)}
                            {--operator=ALL : Opérateur à traiter}
                            {--force : Forcer l\'initialisation même si des données existent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialiser les KPIs Eklektik pour une période donnée';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date') ?: now()->format('Y-m-d');
        $operator = $this->option('operator');
        $force = $this->option('force');

        $this->info("🚀 Initialisation des KPIs Eklektik...");
        $this->info("📅 Période: {$startDate} à {$endDate}");
        $this->info("📱 Opérateur: {$operator}");

        // Vérifier si des données existent déjà
        if (!$force) {
            $existingCount = \App\Models\EklektikKPICache::whereBetween('date', [$startDate, $endDate])
                ->where('operator', $operator)
                ->count();
            
            if ($existingCount > 0) {
                $this->warn("⚠️ Des données existent déjà pour cette période ({$existingCount} entrées).");
                if (!$this->confirm('Voulez-vous continuer ? Cela va recalculer les KPIs.')) {
                    $this->info('❌ Initialisation annulée.');
                    return;
                }
            }
        }

        $startTime = microtime(true);

        try {
            $optimizer = new EklektikKPIOptimizer();
            $optimizer->initializeKPIs($startDate, $endDate, $operator);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info("✅ Initialisation terminée avec succès !");
            $this->info("⏱️ Durée: {$duration} secondes");

            // Afficher les statistiques
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

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de l'initialisation: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
        }
    }
}
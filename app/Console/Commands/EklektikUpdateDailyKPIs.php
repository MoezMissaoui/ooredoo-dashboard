<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EklektikKPIOptimizer;

class EklektikUpdateDailyKPIs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eklektik:update-daily-kpis 
                            {--date= : Date à traiter (défaut: hier)}
                            {--operator=ALL : Opérateur à traiter}
                            {--all-operators : Traiter tous les opérateurs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mettre à jour les KPIs Eklektik pour une journée';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ?: now()->subDay()->format('Y-m-d');
        $operator = $this->option('operator');
        $allOperators = $this->option('all-operators');

        $this->info("📊 Mise à jour des KPIs Eklektik...");
        $this->info("📅 Date: {$date}");

        $startTime = microtime(true);

        try {
            $optimizer = new EklektikKPIOptimizer();

            if ($allOperators) {
                $operators = ['ALL', 'TT', 'Orange', 'Taraji', 'Timwe'];
                $this->info("📱 Opérateurs: " . implode(', ', $operators));
                
                foreach ($operators as $op) {
                    $this->info("🔄 Traitement de l'opérateur: {$op}");
                    $optimizer->updateDailyKPIs($date, $op);
                }
            } else {
                $this->info("📱 Opérateur: {$operator}");
                $optimizer->updateDailyKPIs($date, $operator);
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info("✅ Mise à jour terminée avec succès !");
            $this->info("⏱️ Durée: {$duration} secondes");

            // Afficher les statistiques
            $stats = $optimizer->getProcessingStats($date, $date);
            $this->table(
                ['Métrique', 'Valeur'],
                [
                    ['Notifications traitées', $stats['total_processed']],
                    ['KPIs mis à jour', $stats['kpis_updated_count']],
                    ['Batches uniques', $stats['unique_batches_count']],
                    ['Dernière mise à jour', $stats['last_processing_update'] ?? 'N/A']
                ]
            );

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la mise à jour: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
        }
    }
}
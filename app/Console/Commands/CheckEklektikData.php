<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckEklektikData extends Command
{
    protected $signature = 'eklektik:check-data {--date= : Date spécifique (YYYY-MM-DD)}';
    protected $description = 'Vérifier les données Eklektik synchronisées';

    public function handle()
    {
        $date = $this->option('date') ?: now()->format('Y-m-d');
        
        $this->info("🔍 Vérification des données Eklektik pour le $date");
        $this->info('================================================');
        $this->newLine();

        $stats = DB::table('eklektik_stats_daily')
            ->where('date', $date)
            ->orderBy('operator')
            ->orderBy('offre_id')
            ->get();

        if ($stats->isEmpty()) {
            $this->warn("Aucune donnée trouvée pour le $date");
            return;
        }

        $this->info("📊 Données trouvées: " . $stats->count() . " enregistrements");
        $this->newLine();

        // Grouper par opérateur
        $byOperator = $stats->groupBy('operator');

        foreach ($byOperator as $operator => $operatorStats) {
            $this->info("🎯 $operator:");
            
            foreach ($operatorStats as $stat) {
                $this->info("  📦 ID {$stat->offre_id}: {$stat->offer_name}");
                $this->info("    - Nouveaux: {$stat->new_subscriptions}");
                $this->info("    - Désabonnements: {$stat->unsubscriptions}");
                $this->info("    - Simchurn: {$stat->simchurn}");
                $this->info("    - Facturations: {$stat->nb_facturation}");
                $this->info("    - Taux facturation: {$stat->billing_rate}%");
                $this->info("    - Revenus TTC TND: " . number_format($stat->revenu_ttc_tnd, 2) . " TND");
                $this->info("    - Revenus TTC USD: " . number_format($stat->revenu_ttc_usd, 2) . " USD");
                $this->info("    - Type offre: {$stat->offer_type}");
                $this->newLine();
            }
        }

        // Résumé par opérateur
        $this->info("📈 Résumé par opérateur:");
        foreach ($byOperator as $operator => $operatorStats) {
            $totalNew = $operatorStats->sum('new_subscriptions');
            $totalUnsub = $operatorStats->sum('unsubscriptions');
            $totalSimchurn = $operatorStats->sum('simchurn');
            $totalFacturation = $operatorStats->sum('nb_facturation');
            $totalRevenus = $operatorStats->sum('revenu_ttc_tnd');
            
            $this->info("  $operator:");
            $this->info("    - Nouveaux: $totalNew");
            $this->info("    - Désabonnements: $totalUnsub");
            $this->info("    - Simchurn: $totalSimchurn");
            $this->info("    - Facturations: $totalFacturation");
            $this->info("    - Revenus: " . number_format($totalRevenus, 2) . " TND");
            $this->newLine();
        }

        $this->info('✅ Vérification terminée!');
    }
}


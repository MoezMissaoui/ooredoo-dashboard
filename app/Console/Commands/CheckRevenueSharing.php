<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckRevenueSharing extends Command
{
    protected $signature = 'eklektik:check-revenue-sharing {--date= : Date spécifique (YYYY-MM-DD)}';
    protected $description = 'Vérifier le partage des revenus Eklektik';

    public function handle()
    {
        $date = $this->option('date') ?: now()->format('Y-m-d');
        
        $this->info("💰 Vérification du Partage des Revenus Eklektik pour le $date");
        $this->info('==========================================================');
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
            
            $operatorTotal = [
                'ttc' => 0,
                'ht' => 0,
                'ca_operateur' => 0,
                'ca_agregateur' => 0,
                'ca_bigdeal' => 0
            ];
            
            foreach ($operatorStats as $stat) {
                $this->info("  📦 ID {$stat->offre_id}: {$stat->offer_name}");
                $this->info("    - Revenus TTC: " . number_format($stat->revenu_ttc_tnd, 2) . " TND");
                $this->info("    - Montant HT: " . number_format($stat->montant_total_ht, 2) . " TND");
                $this->info("    - Part Opérateur: {$stat->part_operateur}% = " . number_format($stat->ca_operateur, 2) . " TND");
                $this->info("    - Part Agrégateur: {$stat->part_agregateur}% = " . number_format($stat->ca_agregateur, 2) . " TND");
                $this->info("    - Part BigDeal: {$stat->part_bigdeal}% = " . number_format($stat->ca_bigdeal, 2) . " TND");
                $this->newLine();
                
                $operatorTotal['ttc'] += $stat->revenu_ttc_tnd;
                $operatorTotal['ht'] += $stat->montant_total_ht;
                $operatorTotal['ca_operateur'] += $stat->ca_operateur;
                $operatorTotal['ca_agregateur'] += $stat->ca_agregateur;
                $operatorTotal['ca_bigdeal'] += $stat->ca_bigdeal;
            }
            
            $this->info("  📈 Total $operator:");
            $this->info("    - Revenus TTC: " . number_format($operatorTotal['ttc'], 2) . " TND");
            $this->info("    - Montant HT: " . number_format($operatorTotal['ht'], 2) . " TND");
            $this->info("    - CA Opérateur: " . number_format($operatorTotal['ca_operateur'], 2) . " TND");
            $this->info("    - CA Agrégateur: " . number_format($operatorTotal['ca_agregateur'], 2) . " TND");
            $this->info("    - CA BigDeal: " . number_format($operatorTotal['ca_bigdeal'], 2) . " TND");
            
            // Vérification
            $totalCA = $operatorTotal['ca_operateur'] + $operatorTotal['ca_agregateur'] + $operatorTotal['ca_bigdeal'];
            $difference = abs($totalCA - $operatorTotal['ht']);
            $this->info("    - Vérification: " . number_format($totalCA, 2) . " TND (diff: " . number_format($difference, 4) . ")");
            $this->newLine();
        }

        // Totaux globaux
        $globalTotal = [
            'ttc' => $stats->sum('revenu_ttc_tnd'),
            'ht' => $stats->sum('montant_total_ht'),
            'ca_operateur' => $stats->sum('ca_operateur'),
            'ca_agregateur' => $stats->sum('ca_agregateur'),
            'ca_bigdeal' => $stats->sum('ca_bigdeal')
        ];
        
        $this->info("🌍 TOTAUX GLOBAUX:");
        $this->info("  - Revenus TTC: " . number_format($globalTotal['ttc'], 2) . " TND");
        $this->info("  - Montant HT: " . number_format($globalTotal['ht'], 2) . " TND");
        $this->info("  - CA Opérateur: " . number_format($globalTotal['ca_operateur'], 2) . " TND");
        $this->info("  - CA Agrégateur: " . number_format($globalTotal['ca_agregateur'], 2) . " TND");
        $this->info("  - CA BigDeal: " . number_format($globalTotal['ca_bigdeal'], 2) . " TND");
        
        $totalCA = $globalTotal['ca_operateur'] + $globalTotal['ca_agregateur'] + $globalTotal['ca_bigdeal'];
        $difference = abs($totalCA - $globalTotal['ht']);
        $this->info("  - Vérification: " . number_format($totalCA, 2) . " TND (diff: " . number_format($difference, 4) . ")");

        $this->newLine();
        $this->info('✅ Vérification terminée!');
    }
}


<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EklektikRevenueSharingService
{
    /**
     * Calculer le partage des revenus pour un opérateur donné
     */
    public function calculateRevenueSharing($operator, $revenuTtcTnd)
    {
        $montantTotalHt = $this->calculateMontantTotalHt($operator, $revenuTtcTnd);
        $sharingRates = $this->getSharingRates($operator);
        
        return [
            'montant_total_ht' => $montantTotalHt,
            'part_operateur' => $sharingRates['operateur'],
            'part_agregateur' => $sharingRates['agregateur'],
            'part_bigdeal' => $sharingRates['bigdeal'],
            'ca_operateur' => $montantTotalHt * ($sharingRates['operateur'] / 100),
            'ca_agregateur' => $montantTotalHt * ($sharingRates['agregateur'] / 100),
            'ca_bigdeal' => $montantTotalHt * ($sharingRates['bigdeal'] / 100),
        ];
    }

    /**
     * Calculer le Montant Total HT selon la formule de l'opérateur
     */
    private function calculateMontantTotalHt($operator, $revenuTtcTnd)
    {
        switch (strtoupper($operator)) {
            case 'ORANGE':
                // Orange : Montant Total HT = Montant Total TTC / 1,265284
                return $revenuTtcTnd / 1.265284;
                
            case 'TARAJI':
            case 'TT':
                // Taraji et TT : Montant Total HT = ((Montant Total TTC * 0,95) - (Montant Total TTC * 0,02)) / 1,19
                $montantAvecReduction = ($revenuTtcTnd * 0.95) - ($revenuTtcTnd * 0.02);
                return $montantAvecReduction / 1.19;
                
            default:
                Log::warning("Opérateur inconnu pour le calcul des revenus: $operator");
                return $revenuTtcTnd / 1.2; // Formule par défaut
        }
    }

    /**
     * Obtenir les taux de partage selon l'opérateur
     */
    private function getSharingRates($operator)
    {
        switch (strtoupper($operator)) {
            case 'ORANGE':
                return [
                    'operateur' => 45.0,
                    'agregateur' => 10.0,
                    'bigdeal' => 45.0
                ];
                
            case 'TARAJI':
                return [
                    'operateur' => 50.0,
                    'agregateur' => 17.0,
                    'bigdeal' => 33.0
                ];
                
            case 'TT':
                return [
                    'operateur' => 50.0,
                    'agregateur' => 10.0,
                    'bigdeal' => 40.0
                ];
                
            default:
                Log::warning("Opérateur inconnu pour les taux de partage: $operator");
                return [
                    'operateur' => 50.0,
                    'agregateur' => 10.0,
                    'bigdeal' => 40.0
                ];
        }
    }

    /**
     * Mettre à jour les colonnes de partage des revenus pour un enregistrement
     */
    public function updateRevenueSharing($record)
    {
        $sharing = $this->calculateRevenueSharing($record->operator, $record->revenu_ttc_tnd);
        
        return array_merge((array) $record, $sharing);
    }

    /**
     * Calculer les totaux de partage pour une période
     */
    public function calculatePeriodTotals($stats)
    {
        $totals = [
            'total_ht' => 0,
            'ca_operateur' => 0,
            'ca_agregateur' => 0,
            'ca_bigdeal' => 0,
            'by_operator' => []
        ];

        foreach ($stats as $stat) {
            $sharing = $this->calculateRevenueSharing($stat->operator, $stat->revenu_ttc_tnd);
            
            $totals['total_ht'] += $sharing['montant_total_ht'];
            $totals['ca_operateur'] += $sharing['ca_operateur'];
            $totals['ca_agregateur'] += $sharing['ca_agregateur'];
            $totals['ca_bigdeal'] += $sharing['ca_bigdeal'];
            
            if (!isset($totals['by_operator'][$stat->operator])) {
                $totals['by_operator'][$stat->operator] = [
                    'total_ht' => 0,
                    'ca_operateur' => 0,
                    'ca_agregateur' => 0,
                    'ca_bigdeal' => 0,
                    'records' => 0
                ];
            }
            
            $totals['by_operator'][$stat->operator]['total_ht'] += $sharing['montant_total_ht'];
            $totals['by_operator'][$stat->operator]['ca_operateur'] += $sharing['ca_operateur'];
            $totals['by_operator'][$stat->operator]['ca_agregateur'] += $sharing['ca_agregateur'];
            $totals['by_operator'][$stat->operator]['ca_bigdeal'] += $sharing['ca_bigdeal'];
            $totals['by_operator'][$stat->operator]['records']++;
        }

        return $totals;
    }

    /**
     * Valider les formules de calcul
     */
    public function validateFormulas()
    {
        $testCases = [
            ['operator' => 'Orange', 'ttc' => 1000, 'expected_ht' => 1000 / 1.265284],
            ['operator' => 'Taraji', 'ttc' => 1000, 'expected_ht' => ((1000 * 0.95) - (1000 * 0.02)) / 1.19],
            ['operator' => 'TT', 'ttc' => 1000, 'expected_ht' => ((1000 * 0.95) - (1000 * 0.02)) / 1.19],
        ];

        $results = [];
        foreach ($testCases as $case) {
            $calculated = $this->calculateMontantTotalHt($case['operator'], $case['ttc']);
            $results[] = [
                'operator' => $case['operator'],
                'ttc' => $case['ttc'],
                'calculated_ht' => $calculated,
                'expected_ht' => $case['expected_ht'],
                'difference' => abs($calculated - $case['expected_ht']),
                'is_correct' => abs($calculated - $case['expected_ht']) < 0.01
            ];
        }

        return $results;
    }
}


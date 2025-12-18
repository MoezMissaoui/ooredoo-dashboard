<?php

namespace App\Services;

use App\Models\OoredooDailyStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OoredooStatsService
{
    /**
     * Calculer et stocker les statistiques pour une date donnée
     */
    public function calculateAndStoreStatsForDate(Carbon $date): void
    {
        try {
            Log::info("OoredooStatsService - Calcul pour {$date->format('Y-m-d')}");

            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            // 1. Nouveaux abonnements (OOREDOO_PAYMENT_SUCCESS)
            $newSubs = $this->getNewSubscriptions($startOfDay, $endOfDay);

            // 2. Désabonnements
            $unsubs = $this->getUnsubscriptions($startOfDay, $endOfDay);

            // 3. Abonnements actifs à la fin de la journée
            $activeSubs = $this->getActiveSubscriptions($endOfDay);

            // 4. Total clients actifs
            $totalClients = $this->getTotalActiveClients($endOfDay);

            // 5. Facturations (INVOICE)
            $billings = $this->getBillings($startOfDay, $endOfDay);

            // 6. Revenus
            $revenue = $this->getRevenue($startOfDay, $endOfDay);

            // 7. Taux de facturation
            $billingRate = $totalClients > 0 ? ($billings / $totalClients) * 100 : 0;

            // 8. Répartition par offre
            $offersBreakdown = $this->getOffersBreakdown($startOfDay, $endOfDay);

            // Stocker les statistiques
            OoredooDailyStat::updateOrCreate(
                ['stat_date' => $date->format('Y-m-d')],
                [
                    'new_subscriptions' => $newSubs,
                    'unsubscriptions' => $unsubs,
                    'active_subscriptions' => $activeSubs,
                    'total_billings' => $billings,
                    'billing_rate' => round($billingRate, 2),
                    'revenue_tnd' => $revenue,
                    'total_clients' => $totalClients,
                    'offers_breakdown' => $offersBreakdown,
                ]
            );

            Log::info("OoredooStatsService - Statistiques stockées avec succès", [
                'date' => $date->format('Y-m-d'),
                'new_subs' => $newSubs,
                'billings' => $billings,
                'revenue' => $revenue
            ]);

        } catch (\Exception $e) {
            Log::error("OoredooStatsService - Erreur: " . $e->getMessage(), [
                'date' => $date->format('Y-m-d'),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Nouveaux abonnements (OOREDOO_PAYMENT_SUCCESS)
     * Format ancien : {"event": "Subscription", ...}
     * Format nouveau : {"type": "SUBSCRIPTION", "status": "SUCCESS", ...}
     */
    private function getNewSubscriptions(Carbon $start, Carbon $end): int
    {
        // Compter tous les OOREDOO_PAYMENT_SUCCESS (ancien et nouveau format)
        return DB::table('transactions_history')
            ->where('status', 'OOREDOO_PAYMENT_SUCCESS')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * Désabonnements
     */
    private function getUnsubscriptions(Carbon $start, Carbon $end): int
    {
        return DB::table('transactions_history')
            ->whereIn('status', [
                'OOREDOO_PAYMENT_UNSUBSCRIBE',
                'OOREDOO_UNSUBSCRIBE',
                'DELAYED_OOREDOO_PAYMENT_UNSUBSCRIBE'
            ])
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * Abonnements actifs (reconstruits depuis les transactions)
     * Approche simplifiée : Abonnements SUCCESS - Désabonnements jusqu'à cette date
     */
    private function getActiveSubscriptions(Carbon $endOfDay): int
    {
        // Total abonnements créés jusqu'à cette date
        $totalSuccess = DB::table('transactions_history')
            ->where('status', 'OOREDOO_PAYMENT_SUCCESS')
            ->where('created_at', '<=', $endOfDay)
            ->count();
        
        // Total désabonnements jusqu'à cette date
        $totalUnsub = DB::table('transactions_history')
            ->whereIn('status', ['OOREDOO_PAYMENT_UNSUBSCRIBE', 'OOREDOO_UNSUBSCRIBE', 'DELAYED_OOREDOO_PAYMENT_UNSUBSCRIBE'])
            ->where('created_at', '<=', $endOfDay)
            ->count();
        
        return max(0, $totalSuccess - $totalUnsub);
    }

    /**
     * Total clients actifs uniques (reconstruit depuis les transactions)
     * Approche SQL pure pour éviter les dépassements de mémoire
     * Format ancien : $.msisdn
     * Format nouveau : $.user.msisdn
     */
    private function getTotalActiveClients(Carbon $endOfDay): int
    {
        // Compter les msisdns uniques dans les PAYMENT_SUCCESS
        $successCount = DB::select("
            SELECT COUNT(DISTINCT COALESCE(
                JSON_EXTRACT(result, '$.msisdn'),
                JSON_EXTRACT(result, '$.user.msisdn')
            )) as count
            FROM transactions_history
            WHERE status = 'OOREDOO_PAYMENT_SUCCESS'
            AND created_at <= ?
            AND JSON_VALID(result) = 1
        ", [$endOfDay])[0]->count ?? 0;
        
        // Compter les msisdns uniques dans les désabonnements
        $unsubCount = DB::select("
            SELECT COUNT(DISTINCT COALESCE(
                JSON_EXTRACT(result, '$.msisdn'),
                JSON_EXTRACT(result, '$.user.msisdn')
            )) as count
            FROM transactions_history
            WHERE status IN ('OOREDOO_PAYMENT_UNSUBSCRIBE', 'OOREDOO_UNSUBSCRIBE', 'DELAYED_OOREDOO_PAYMENT_UNSUBSCRIBE')
            AND created_at <= ?
            AND JSON_VALID(result) = 1
        ", [$endOfDay])[0]->count ?? 0;
        
        // Estimation : clients actifs ≈ clients avec succès - clients désabonnés
        // (ce n'est pas parfait car un client peut s'abonner plusieurs fois, mais c'est une approximation acceptable)
        return max(0, $successCount - $unsubCount);
    }

    /**
     * Facturations
     * Avant 01/09/2025 : OOREDOO_PAYMENT_OFFLINE (result=null, compter par statut)
     * Après 01/09/2025 : OOREDOO_PAYMENT_OFFLINE_INIT avec type="INVOICE"
     */
    private function getBillings(Carbon $start, Carbon $end): int
    {
        $cutoffDate = Carbon::parse('2025-09-01');
        
        if ($end < $cutoffDate) {
            // Période avant 01/09/2025 : utiliser OOREDOO_PAYMENT_OFFLINE uniquement
            return DB::table('transactions_history')
                ->where('status', 'OOREDOO_PAYMENT_OFFLINE')
                ->whereBetween('created_at', [$start, $end])
                ->count();
        } elseif ($start >= $cutoffDate) {
            // Période après 01/09/2025 : utiliser OOREDOO_PAYMENT_OFFLINE_INIT avec type=INVOICE
            return DB::table('transactions_history')
                ->where('status', 'OOREDOO_PAYMENT_OFFLINE_INIT')
                ->whereBetween('created_at', [$start, $end])
                ->whereRaw("JSON_EXTRACT(result, '$.type') = 'INVOICE'")
                ->whereRaw("JSON_EXTRACT(result, '$.status') = 'SUCCESS'")
                ->count();
        } else {
            // Période à cheval sur la date de coupure : compter les deux
            $before = DB::table('transactions_history')
                ->where('status', 'OOREDOO_PAYMENT_OFFLINE')
                ->whereBetween('created_at', [$start, $cutoffDate->copy()->subSecond()])
                ->count();
                
            $after = DB::table('transactions_history')
                ->where('status', 'OOREDOO_PAYMENT_OFFLINE_INIT')
                ->whereBetween('created_at', [$cutoffDate, $end])
                ->whereRaw("JSON_EXTRACT(result, '$.type') = 'INVOICE'")
                ->whereRaw("JSON_EXTRACT(result, '$.status') = 'SUCCESS'")
                ->count();
                
            return $before + $after;
        }
    }

    /**
     * Revenus totaux
     * Avant 01/09/2025 : Pas de données de revenus (result=null)
     * Après 01/09/2025 : Extraire invoice.price depuis OOREDOO_PAYMENT_OFFLINE_INIT
     */
    private function getRevenue(Carbon $start, Carbon $end): float
    {
        $cutoffDate = Carbon::parse('2025-09-01');
        
        // Les revenus ne sont disponibles qu'à partir du 01/09/2025
        if ($end < $cutoffDate) {
            return 0.0;
        }
        
        $startDate = $start >= $cutoffDate ? $start : $cutoffDate;
        
        $transactions = DB::table('transactions_history')
            ->where('status', 'OOREDOO_PAYMENT_OFFLINE_INIT')
            ->whereBetween('created_at', [$startDate, $end])
            ->whereRaw("JSON_EXTRACT(result, '$.type') = 'INVOICE'")
            ->whereRaw("JSON_EXTRACT(result, '$.status') = 'SUCCESS'")
            ->pluck('result');

        $totalRevenue = 0;
        foreach ($transactions as $resultJson) {
            $result = json_decode($resultJson, true);
            if (isset($result['invoice']['price'])) {
                $totalRevenue += (float) $result['invoice']['price'];
            }
        }

        return $totalRevenue;
    }

    /**
     * Répartition par offre
     * Format ancien : {"event": "Subscription", "service_id": "25985", ...}
     * Format nouveau : {"type": "SUBSCRIPTION", "offer": {"commercialName": "...", "id": 4066}, ...}
     */
    private function getOffersBreakdown(Carbon $start, Carbon $end): array
    {
        $transactions = DB::table('transactions_history')
            ->where('status', 'OOREDOO_PAYMENT_SUCCESS')
            ->whereBetween('created_at', [$start, $end])
            ->pluck('result');

        $offers = [];
        foreach ($transactions as $resultJson) {
            $result = json_decode($resultJson, true);
            
            // Nouveau format (>= 2025)
            if (isset($result['offer']['commercialName'])) {
                $offerName = $result['offer']['commercialName'];
                $offerId = $result['offer']['id'] ?? 0;
            }
            // Ancien format (<2025) - utiliser un nom par défaut
            elseif (isset($result['event']) && $result['event'] === 'Subscription') {
                $offerName = 'Club Privilèges';
                $offerId = 4066; // ID par défaut
            }
            else {
                continue;
            }
            
            if (!isset($offers[$offerName])) {
                $offers[$offerName] = [
                    'offre_name' => $offerName,
                    'offre_id' => $offerId,
                    'count' => 0
                ];
            }
            $offers[$offerName]['count']++;
        }

        return array_values($offers);
    }
}


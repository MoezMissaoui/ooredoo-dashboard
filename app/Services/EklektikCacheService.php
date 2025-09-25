<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\EklektikStatsDaily;

class EklektikCacheService
{
    private $cachePrefix = 'eklektik_stats_';
    private $cacheDuration = 300; // 5 minutes

    /**
     * Récupérer les KPIs Eklektik avec cache
     */
    public function getCachedKPIs($startDate, $endDate, $operator = null)
    {
        $cacheKey = $this->cachePrefix . 'kpis_' . md5($startDate . $endDate . $operator);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($startDate, $endDate, $operator) {
            return $this->calculateKPIs($startDate, $endDate, $operator);
        });
    }

    /**
     * Récupérer les statistiques détaillées avec cache
     */
    public function getCachedDetailedStats($startDate, $endDate, $operator = null)
    {
        $cacheKey = $this->cachePrefix . 'detailed_' . md5($startDate . $endDate . $operator);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($startDate, $endDate, $operator) {
            return $this->getDetailedStats($startDate, $endDate, $operator);
        });
    }

    /**
     * Récupérer la répartition par opérateur avec cache
     */
    public function getCachedOperatorsDistribution($startDate, $endDate)
    {
        $cacheKey = $this->cachePrefix . 'operators_' . md5($startDate . $endDate);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($startDate, $endDate) {
            return $this->getOperatorsDistribution($startDate, $endDate);
        });
    }

    /**
     * Récupérer les revenus BigDeal avec cache
     */
    public function getCachedBigDealRevenue($startDate, $endDate, $operator = null)
    {
        $cacheKey = $this->cachePrefix . 'bigdeal_' . md5($startDate . $endDate . $operator);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($startDate, $endDate, $operator) {
            return $this->getBigDealRevenue($startDate, $endDate, $operator);
        });
    }

    /**
     * Calculer les KPIs Eklektik
     */
    private function calculateKPIs($startDate, $endDate, $operator = null)
    {
        $query = DB::table('eklektik_stats_daily')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($operator && $operator !== 'ALL') {
            $query->where('operator', $operator);
        }

        $stats = $query->get();

        if ($stats->isEmpty()) {
            return [
                'total_new_subscriptions' => 0,
                'total_unsubscriptions' => 0,
                'total_simchurn' => 0,
                'total_facturation' => 0,
                'total_revenue_ttc' => 0,
                'total_revenue_ht' => 0,
                'total_ca_operateur' => 0,
                'total_ca_agregateur' => 0,
                'total_ca_bigdeal' => 0,
                'average_billing_rate' => 0,
                'total_active_subscribers' => 0,
                'operators_distribution' => []
            ];
        }

        // Déterminer le snapshot de fin de période pour Active Subs (ou dernier jour disponible)
        $endDayRows = $stats->where('date', $endDate);
        if ($endDayRows->isEmpty()) {
            $latestDate = optional($stats->sortBy('date')->last())->date;
            $endDayRows = $latestDate ? $stats->where('date', $latestDate) : collect();
        }
        $activeByOperator = $endDayRows->groupBy('operator')->map(function ($rows) {
            return $rows->sum('active_subscribers');
        })->toArray();

        $kpis = [
            'total_new_subscriptions' => $stats->sum('new_subscriptions'),
            'total_unsubscriptions' => $stats->sum('unsubscriptions'),
            'total_simchurn' => $stats->sum('simchurn'),
            'total_facturation' => $stats->sum('nb_facturation'),
            'total_revenue_ttc' => $stats->sum('revenu_ttc_tnd'),
            'total_revenue_ht' => $stats->sum('montant_total_ht'),
            'total_ca_operateur' => $stats->sum('ca_operateur'),
            'total_ca_agregateur' => $stats->sum('ca_agregateur'),
            'total_ca_bigdeal' => $stats->sum('ca_bigdeal'),
            'average_billing_rate' => $stats->avg('billing_rate'),
            // Active subs = somme du snapshot de fin
            'total_active_subscribers' => $endDayRows->sum('active_subscribers'),
            'active_subscribers_by_operator' => $activeByOperator,
            'operators_distribution' => []
        ];

        // Calculer la répartition par opérateur
        $operators = $stats->groupBy('operator');
        foreach ($operators as $operatorName => $operatorStats) {
            $kpis['operators_distribution'][$operatorName] = [
                'total_records' => $operatorStats->count(),
                'new_subscriptions' => $operatorStats->sum('new_subscriptions'),
                'unsubscriptions' => $operatorStats->sum('unsubscriptions'),
                'simchurn' => $operatorStats->sum('simchurn'),
                'facturation' => $operatorStats->sum('nb_facturation'),
                'active_subscribers' => $operatorStats->where('date', $endDayRows->first()->date ?? $endDate)->sum('active_subscribers'),
                'revenue_ttc' => $operatorStats->sum('revenu_ttc_tnd'),
                'revenue_ht' => $operatorStats->sum('montant_total_ht'),
                'ca_operateur' => $operatorStats->sum('ca_operateur'),
                'ca_agregateur' => $operatorStats->sum('ca_agregateur'),
                'ca_bigdeal' => $operatorStats->sum('ca_bigdeal')
            ];
        }

        return $kpis;
    }

    /**
     * Récupérer les statistiques détaillées
     */
    private function getDetailedStats($startDate, $endDate, $operator = null)
    {
        $query = DB::table('eklektik_stats_daily')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($operator && $operator !== 'ALL') {
            $query->where('operator', $operator);
        }

        $stats = $query->orderBy('date', 'desc')->get();

        // Grouper par date pour l'évolution temporelle
        $dailyStats = $stats->groupBy('date')->map(function ($dayStats) {
            return [
                'date' => $dayStats->first()->date,
                // Somme réelle des abonnés actifs pour la date
                'total_active_subscribers' => $dayStats->sum('active_subscribers'),
                'total_new_subscriptions' => $dayStats->sum('new_subscriptions'),
                'total_unsubscriptions' => $dayStats->sum('unsubscriptions'),
                'total_simchurn' => $dayStats->sum('simchurn'),
                'total_facturation' => $dayStats->sum('nb_facturation'),
                'total_revenue_ttc' => $dayStats->sum('revenu_ttc_tnd'),
                'total_revenue_ht' => $dayStats->sum('montant_total_ht'),
                'total_ca_operateur' => $dayStats->sum('ca_operateur'),
                'total_ca_agregateur' => $dayStats->sum('ca_agregateur'),
                'total_ca_bigdeal' => $dayStats->sum('ca_bigdeal'),
                'average_billing_rate' => $dayStats->avg('billing_rate'),
                'operators' => $dayStats->map(function ($stat) {
                    return [
                        'operator' => $stat->operator,
                        'offre_id' => $stat->offre_id,
                        'offer_name' => $stat->offer_name,
                        'new_subscriptions' => $stat->new_subscriptions,
                        'unsubscriptions' => $stat->unsubscriptions,
                        'simchurn' => $stat->simchurn,
                        'facturation' => $stat->nb_facturation,
                        'revenue_ttc' => $stat->revenu_ttc_tnd,
                        'revenue_ht' => $stat->montant_total_ht,
                        'ca_operateur' => $stat->ca_operateur,
                        'ca_agregateur' => $stat->ca_agregateur,
                        'ca_bigdeal' => $stat->ca_bigdeal
                    ];
                })->values()
            ];
        })->values();

        return $dailyStats;
    }

    /**
     * Récupérer la répartition par opérateur
     */
    private function getOperatorsDistribution($startDate, $endDate)
    {
        $stats = DB::table('eklektik_stats_daily')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $operators = $stats->groupBy('operator');
        $distribution = [];

        foreach ($operators as $operatorName => $operatorStats) {
            $distribution[$operatorName] = [
                'total_records' => $operatorStats->count(),
                'new_subscriptions' => $operatorStats->sum('new_subscriptions'),
                'unsubscriptions' => $operatorStats->sum('unsubscriptions'),
                'simchurn' => $operatorStats->sum('simchurn'),
                'facturation' => $operatorStats->sum('nb_facturation'),
                'revenue_ttc' => $operatorStats->sum('revenu_ttc_tnd'),
                'revenue_ht' => $operatorStats->sum('montant_total_ht'),
                'ca_operateur' => $operatorStats->sum('ca_operateur'),
                'ca_agregateur' => $operatorStats->sum('ca_agregateur'),
                'ca_bigdeal' => $operatorStats->sum('ca_bigdeal'),
                'offers' => $operatorStats->groupBy('offre_id')->map(function ($offerStats) {
                    $offer = $offerStats->first();
                    return [
                        'offre_id' => $offer->offre_id,
                        'offer_name' => $offer->offer_name,
                        'offer_type' => $offer->offer_type,
                        'new_subscriptions' => $offerStats->sum('new_subscriptions'),
                        'unsubscriptions' => $offerStats->sum('unsubscriptions'),
                        'simchurn' => $offerStats->sum('simchurn'),
                        'facturation' => $offerStats->sum('nb_facturation'),
                        'revenue_ttc' => $offerStats->sum('revenu_ttc_tnd'),
                        'revenue_ht' => $offerStats->sum('montant_total_ht'),
                        'ca_operateur' => $offerStats->sum('ca_operateur'),
                        'ca_agregateur' => $offerStats->sum('ca_agregateur'),
                        'ca_bigdeal' => $offerStats->sum('ca_bigdeal')
                    ];
                })->values()
            ];
        }

        return $distribution;
    }

    /**
     * Récupérer les revenus BigDeal
     */
    private function getBigDealRevenue($startDate, $endDate, $operator = null)
    {
        $query = DB::table('eklektik_stats_daily')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($operator && $operator !== 'ALL') {
            $query->where('operator', $operator);
        }

        $stats = $query->get();

        return [
            'total_ca_bigdeal' => $stats->sum('ca_bigdeal'),
            'total_revenue_ht' => $stats->sum('montant_total_ht'),
            'bigdeal_percentage' => $stats->sum('montant_total_ht') > 0 ? 
                ($stats->sum('ca_bigdeal') / $stats->sum('montant_total_ht')) * 100 : 0,
            'by_operator' => $stats->groupBy('operator')->map(function ($operatorStats) {
                return [
                    'ca_bigdeal' => $operatorStats->sum('ca_bigdeal'),
                    'revenue_ht' => $operatorStats->sum('montant_total_ht'),
                    'percentage' => $operatorStats->sum('montant_total_ht') > 0 ? 
                        ($operatorStats->sum('ca_bigdeal') / $operatorStats->sum('montant_total_ht')) * 100 : 0
                ];
            })
        ];
    }

    /**
     * Vider le cache Eklektik
     */
    public function clearCache()
    {
        // Vider le cache en supprimant les clés connues
        $knownKeys = [
            $this->cachePrefix . 'kpis_',
            $this->cachePrefix . 'detailed_',
            $this->cachePrefix . 'operators_',
            $this->cachePrefix . 'bigdeal_'
        ];
        
        $clearedCount = 0;
        foreach ($knownKeys as $keyPattern) {
            // Pour le cache de fichiers, on ne peut pas facilement lister les clés
            // On va simplement vider le cache complet
            Cache::flush();
            $clearedCount = 1; // Indique qu'on a vidé le cache
            break;
        }
        
        return $clearedCount;
    }

    /**
     * Obtenir les statistiques de cache
     */
    public function getCacheStats()
    {
        // Pour le cache de fichiers, on ne peut pas facilement obtenir les statistiques
        // On retourne des informations basiques
        return [
            [
                'key' => 'eklektik_cache_info',
                'ttl' => $this->cacheDuration,
                'expires_in' => Carbon::now()->addSeconds($this->cacheDuration)->diffForHumans(),
                'note' => 'Cache de fichiers - statistiques limitées'
            ]
        ];
    }

    /**
     * Récupérer l'évolution des revenus par opérateur
     */
    public function getCachedOperatorsRevenueEvolution($startDate, $endDate)
    {
        $cacheKey = "eklektik_operators_revenue_evolution_{$startDate}_{$endDate}";
        
        return Cache::remember($cacheKey, $this->cacheDuration, function() use ($startDate, $endDate) {
            $stats = EklektikStatsDaily::whereBetween('date', [$startDate, $endDate])
                ->selectRaw('
                    date,
                    SUM(CASE WHEN operator = "TT" THEN ca_bigdeal ELSE 0 END) as tt_revenue,
                    SUM(CASE WHEN operator = "Taraji" THEN ca_bigdeal ELSE 0 END) as taraji_revenue,
                    SUM(CASE WHEN operator = "Orange" THEN ca_bigdeal ELSE 0 END) as orange_revenue,
                    SUM(ca_bigdeal) as total_ca_bigdeal
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return $stats->map(function($stat) {
                return [
                    'date' => $stat->date,
                    'tt_revenue' => (float) $stat->tt_revenue,
                    'taraji_revenue' => (float) $stat->taraji_revenue,
                    'orange_revenue' => (float) $stat->orange_revenue,
                    'total_ca_bigdeal' => (float) $stat->total_ca_bigdeal,
                ];
            });
        });
    }
}

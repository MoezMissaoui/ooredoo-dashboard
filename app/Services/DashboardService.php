<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Configuration du cache adaptatif selon la période
     */
    private function getCacheTTL(int $periodDays): int
    {
        if ($periodDays <= 7) {
            return 300; // 5 minutes pour les périodes courtes
        } elseif ($periodDays <= 30) {
            return 900; // 15 minutes pour les périodes moyennes
        } elseif ($periodDays <= 90) {
            return 1800; // 30 minutes pour les périodes longues
        } else {
            return 7200; // 2 heures pour les très longues périodes
        }
    }
    
    /**
     * Génère une clé de cache optimisée (sans user_id pour partage)
     */
    private function generateCacheKey(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $operator): string
    {
        $keyData = [
            'dashboard_v2',
            $startDate,
            $endDate,
            $comparisonStartDate,
            $comparisonEndDate,
            $operator
        ];
        
        return 'dashboard:' . md5(implode(':', $keyData));
    }
    
    /**
     * Récupère les données du dashboard avec optimisations
     */
    public function getDashboardData(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $selectedOperator): array
    {
        $startTime = microtime(true);
        
        // Calcul de la période et TTL adaptatif
        $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        $cacheTTL = $this->getCacheTTL($periodDays);
        $cacheKey = $this->generateCacheKey($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedOperator);
        
        Log::info("DashboardService: Période de {$periodDays} jours, TTL cache: {$cacheTTL}s");
        
        return Cache::remember($cacheKey, $cacheTTL, function () use ($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedOperator, $periodDays, $startTime) {
            
            if ($periodDays > 90) {
                Log::info("Mode optimisé activé pour période longue");
                return $this->getOptimizedDashboardData($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedOperator, $startTime);
            }
            
            return $this->getStandardDashboardData($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedOperator, $startTime);
        });
    }
    
    /**
     * Mode standard pour périodes courtes/moyennes
     */
    private function getStandardDashboardData(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $selectedOperator, float $startTime): array
    {
        // Normalisation des dates
        $startBound = Carbon::parse($startDate)->startOfDay();
        $endExclusive = Carbon::parse($endDate)->addDay()->startOfDay();
        $compStartBound = Carbon::parse($comparisonStartDate)->startOfDay();
        $compEndExclusive = Carbon::parse($comparisonEndDate)->addDay()->startOfDay();
        
        // 1. KPIs principaux avec requêtes optimisées
        $kpis = $this->getKPIsOptimized($startBound, $endExclusive, $compStartBound, $compEndExclusive, $selectedOperator);
        
        // 2. Marchands avec correction du problème N+1
        $merchants = $this->getMerchantsOptimized($startBound, $endExclusive, $compStartBound, $compEndExclusive, $selectedOperator);
        
        // 3. Données de transactions agrégées
        $transactions = $this->getTransactionsData($startBound, $endExclusive, $selectedOperator);
        
        // 4. Données d'abonnements
        $subscriptions = $this->getSubscriptionsData($startBound, $endExclusive, $selectedOperator);
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            "periods" => [
                "primary" => Carbon::parse($startDate)->format("M j, Y") . " - " . Carbon::parse($endDate)->format("M j, Y"),
                "comparison" => Carbon::parse($comparisonStartDate)->format("M j, Y") . " - " . Carbon::parse($comparisonEndDate)->format("M j, Y")
            ],
            "kpis" => $kpis,
            "merchants" => $merchants['data'],
            "categoryDistribution" => $merchants['categories'],
            "transactions" => $transactions,
            "subscriptions" => $subscriptions,
            "insights" => $this->generateInsights($kpis, $merchants['data']),
            "last_updated" => now()->toISOString(),
            "data_source" => "optimized_database",
            "execution_time_ms" => $executionTime,
            "cache_mode" => "standard"
        ];
    }
    
    /**
     * KPIs optimisés avec requêtes unifiées
     */
    private function getKPIsOptimized(Carbon $startBound, Carbon $endExclusive, Carbon $compStartBound, Carbon $compEndExclusive, string $selectedOperator): array
    {
        // Requête unifiée pour tous les KPIs d'abonnements
        $subscriptionQuery = DB::table('client_abonnement as ca')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->select([
                // Période principale
                DB::raw("COUNT(CASE WHEN ca.client_abonnement_creation >= '{$startBound}' AND ca.client_abonnement_creation < '{$endExclusive}' THEN 1 END) as activated_current"),
                DB::raw("COUNT(CASE WHEN ca.client_abonnement_creation >= '{$startBound}' AND ca.client_abonnement_creation < '{$endExclusive}' AND (ca.client_abonnement_expiration IS NULL OR ca.client_abonnement_expiration >= '{$endExclusive}') THEN 1 END) as active_current"),
                DB::raw("COUNT(CASE WHEN ca.client_abonnement_expiration >= '{$startBound}' AND ca.client_abonnement_expiration < '{$endExclusive}' THEN 1 END) as deactivated_current"),
                
                // Période de comparaison
                DB::raw("COUNT(CASE WHEN ca.client_abonnement_creation >= '{$compStartBound}' AND ca.client_abonnement_creation < '{$compEndExclusive}' THEN 1 END) as activated_comparison"),
                DB::raw("COUNT(CASE WHEN ca.client_abonnement_creation >= '{$compStartBound}' AND ca.client_abonnement_creation < '{$compEndExclusive}' AND (ca.client_abonnement_expiration IS NULL OR ca.client_abonnement_expiration >= '{$compEndExclusive}') THEN 1 END) as active_comparison"),
                DB::raw("COUNT(CASE WHEN ca.client_abonnement_expiration >= '{$compStartBound}' AND ca.client_abonnement_expiration < '{$compEndExclusive}' THEN 1 END) as deactivated_comparison")
            ]);
        
        if ($selectedOperator !== 'ALL') {
            $subscriptionQuery->where('cpm.country_payments_methods_name', $selectedOperator);
        }
        
        $subMetrics = $subscriptionQuery->first();
        
        // Requête unifiée pour les transactions
        $transactionQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cmp.country_payments_methods_id')
            ->select([
                DB::raw("COUNT(CASE WHEN h.time >= '{$startBound}' AND h.time < '{$endExclusive}' THEN 1 END) as transactions_current"),
                DB::raw("COUNT(CASE WHEN h.time >= '{$compStartBound}' AND h.time < '{$compEndExclusive}' THEN 1 END) as transactions_comparison"),
                DB::raw("COUNT(DISTINCT CASE WHEN h.time >= '{$startBound}' AND h.time < '{$endExclusive}' THEN ca.client_id END) as users_current"),
                DB::raw("COUNT(DISTINCT CASE WHEN h.time >= '{$compStartBound}' AND h.time < '{$compEndExclusive}' THEN ca.client_id END) as users_comparison")
            ]);
        
        if ($selectedOperator !== 'ALL') {
            $transactionQuery->where('cpm.country_payments_methods_name', $selectedOperator);
        }
        
        $txMetrics = $transactionQuery->first();
        
        // Calculs des taux
        $retentionRate = $subMetrics->activated_current > 0 ? round(($subMetrics->active_current / $subMetrics->activated_current) * 100, 1) : 0;
        $retentionRateComparison = $subMetrics->activated_comparison > 0 ? round(($subMetrics->active_comparison / $subMetrics->activated_comparison) * 100, 1) : 0;
        
        $conversionRate = $subMetrics->active_current > 0 ? round(($txMetrics->users_current / $subMetrics->active_current) * 100, 1) : 0;
        $conversionRateComparison = $subMetrics->active_comparison > 0 ? round(($txMetrics->users_comparison / $subMetrics->active_comparison) * 100, 1) : 0;
        
        return [
            "activatedSubscriptions" => [
                "current" => $subMetrics->activated_current,
                "previous" => $subMetrics->activated_comparison,
                "change" => $this->calculatePercentageChange($subMetrics->activated_current, $subMetrics->activated_comparison)
            ],
            "activeSubscriptions" => [
                "current" => $subMetrics->active_current,
                "previous" => $subMetrics->active_comparison,
                "change" => $this->calculatePercentageChange($subMetrics->active_current, $subMetrics->active_comparison)
            ],
            "totalTransactions" => [
                "current" => $txMetrics->transactions_current,
                "previous" => $txMetrics->transactions_comparison,
                "change" => $this->calculatePercentageChange($txMetrics->transactions_current, $txMetrics->transactions_comparison)
            ],
            "transactingUsers" => [
                "current" => $txMetrics->users_current,
                "previous" => $txMetrics->users_comparison,
                "change" => $this->calculatePercentageChange($txMetrics->users_current, $txMetrics->users_comparison)
            ],
            "retentionRate" => [
                "current" => $retentionRate,
                "previous" => $retentionRateComparison,
                "change" => $this->calculatePercentageChange($retentionRate, $retentionRateComparison)
            ],
            "conversionRate" => [
                "current" => $conversionRate,
                "previous" => $conversionRateComparison,
                "change" => $this->calculatePercentageChange($conversionRate, $conversionRateComparison)
            ]
        ];
    }
    
    /**
     * Marchands optimisés - CORRECTION du problème N+1
     */
    private function getMerchantsOptimized(Carbon $startBound, Carbon $endExclusive, Carbon $compStartBound, Carbon $compEndExclusive, string $selectedOperator): array
    {
        // Requête unifiée pour éviter le N+1
        $merchantsQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->join('promotion as p', 'h.promotion_id', '=', 'p.promotion_id')
            ->join('partner as pt', 'p.partner_id', '=', 'pt.partner_id')
            ->select([
                'pt.partner_name as name',
                'pt.partner_id',
                // Transactions période principale
                DB::raw("COUNT(CASE WHEN h.time >= '{$startBound}' AND h.time < '{$endExclusive}' THEN 1 END) as current"),
                // Transactions période comparaison
                DB::raw("COUNT(CASE WHEN h.time >= '{$compStartBound}' AND h.time < '{$compEndExclusive}' THEN 1 END) as previous")
            ])
            ->whereNotNull('h.promotion_id');
        
        if ($selectedOperator !== 'ALL') {
            $merchantsQuery->where('cpm.country_payments_methods_name', $selectedOperator);
        }
        
        $merchants = $merchantsQuery
            ->groupBy('pt.partner_name', 'pt.partner_id')
            ->having('current', '>', 0) // Seulement les marchands actifs
            ->orderBy('current', 'DESC')
            ->limit(50)
            ->get();
        
        // Total des transactions pour calculer les parts de marché
        $totalTransactions = $merchants->sum('current');
        
        // Enrichissement avec catégories et calculs
        $enrichedMerchants = $merchants->map(function($merchant) use ($totalTransactions) {
            $category = $this->categorizePartner($merchant->name);
            $share = $totalTransactions > 0 ? round(($merchant->current / $totalTransactions) * 100, 1) : 0;
            
            return [
                'name' => $merchant->name ?? 'Unknown',
                'category' => $category,
                'current' => $merchant->current,
                'previous' => $merchant->previous,
                'share' => $share,
                'partner_id' => $merchant->partner_id
            ];
        })->toArray();
        
        // Distribution par catégories
        $categoryDistribution = $this->calculateCategoryDistribution($enrichedMerchants, $totalTransactions);
        
        return [
            'data' => $enrichedMerchants,
            'categories' => $categoryDistribution
        ];
    }
    
    /**
     * Mode optimisé pour très longues périodes
     */
    private function getOptimizedDashboardData(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $selectedOperator, float $startTime): array
    {
        // Implémentation simplifiée pour les très longues périodes
        $startBound = Carbon::parse($startDate)->startOfDay();
        $endExclusive = Carbon::parse($endDate)->addDay()->startOfDay();
        $compStartBound = Carbon::parse($comparisonStartDate)->startOfDay();
        $compEndExclusive = Carbon::parse($comparisonEndDate)->addDay()->startOfDay();
        
        $kpis = $this->getKPIsOptimized($startBound, $endExclusive, $compStartBound, $compEndExclusive, $selectedOperator);
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            "periods" => [
                "primary" => Carbon::parse($startDate)->format("M j, Y") . " - " . Carbon::parse($endDate)->format("M j, Y"),
                "comparison" => Carbon::parse($comparisonStartDate)->format("M j, Y") . " - " . Carbon::parse($comparisonEndDate)->format("M j, Y")
            ],
            "kpis" => $kpis,
            "merchants" => [],
            "categoryDistribution" => [],
            "transactions" => ["daily_volume" => [], "by_category" => []],
            "subscriptions" => ["daily_activations" => [], "retention_trend" => []],
            "insights" => [
                "positive" => ["Mode optimisé activé pour période étendue"],
                "challenges" => ["Analyse détaillée limitée pour optimiser les performances"],
                "recommendations" => ["Réduire la période pour une analyse plus détaillée"],
                "nextSteps" => ["Analyser des sous-périodes spécifiques"]
            ],
            "last_updated" => now()->toISOString(),
            "data_source" => "optimized_database",
            "execution_time_ms" => $executionTime,
            "cache_mode" => "long_period"
        ];
    }
    
    /**
     * Calcul du pourcentage de changement
     */
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    /**
     * Catégorisation des partenaires
     */
    private function categorizePartner(string $partnerName): string
    {
        $name = strtoupper($partnerName);
        
        if (str_contains($name, 'KFC') || str_contains($name, 'RESTAURANT') || str_contains($name, 'PIZZA')) {
            return 'Food & Beverage';
        }
        if (str_contains($name, 'BEAUTY') || str_contains($name, 'SPA') || str_contains($name, 'SALON')) {
            return 'Beauty & Wellness';
        }
        if (str_contains($name, 'CLUB') || str_contains($name, 'BAR') || str_contains($name, 'LOUNGE')) {
            return 'Entertainment';
        }
        if (str_contains($name, 'GYM') || str_contains($name, 'FITNESS') || str_contains($name, 'SPORT')) {
            return 'Fitness & Sports';
        }
        if (str_contains($name, 'SHOP') || str_contains($name, 'STORE') || str_contains($name, 'CENTER')) {
            return 'Retail';
        }
        
        return 'Others';
    }
    
    /**
     * Calcul de la distribution par catégories
     */
    private function calculateCategoryDistribution(array $merchants, int $totalTransactions): array
    {
        $categories = [];
        
        foreach ($merchants as $merchant) {
            $category = $merchant['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = ['transactions' => 0, 'merchants' => 0];
            }
            $categories[$category]['transactions'] += $merchant['current'];
            $categories[$category]['merchants']++;
        }
        
        $distribution = [];
        foreach ($categories as $category => $data) {
            $percentage = $totalTransactions > 0 ? round(($data['transactions'] / $totalTransactions) * 100, 1) : 0;
            $distribution[] = [
                'category' => $category,
                'transactions' => $data['transactions'],
                'merchants' => $data['merchants'],
                'percentage' => $percentage
            ];
        }
        
        // Trier par nombre de transactions décroissant
        usort($distribution, function($a, $b) {
            return $b['transactions'] - $a['transactions'];
        });
        
        return $distribution;
    }
    
    /**
     * Génération d'insights basés sur les données
     */
    private function generateInsights(array $kpis, array $merchants): array
    {
        $positive = [];
        $challenges = [];
        $recommendations = [];
        
        // Insights positifs
        if ($kpis['activatedSubscriptions']['change'] > 50) {
            $positive[] = "Excellente croissance des abonnements (+{$kpis['activatedSubscriptions']['change']}%)";
        }
        if ($kpis['retentionRate']['current'] > 80) {
            $positive[] = "Taux de rétention élevé de {$kpis['retentionRate']['current']}%";
        }
        
        // Défis
        if ($kpis['conversionRate']['current'] < 10) {
            $challenges[] = "Taux de conversion faible ({$kpis['conversionRate']['current']}%) à améliorer";
        }
        if (count($merchants) < 5) {
            $challenges[] = "Réseau de marchands limité (" . count($merchants) . " actifs)";
        }
        
        // Recommandations
        $recommendations[] = "Optimiser l'expérience utilisateur pour améliorer la conversion";
        $recommendations[] = "Développer le réseau de partenaires marchands";
        
        return [
            "positive" => $positive,
            "challenges" => $challenges,
            "recommendations" => $recommendations,
            "nextSteps" => ["Analyser les parcours utilisateurs", "Lancer des campagnes d'engagement"]
        ];
    }
    
    /**
     * Données de transactions (implémentation simplifiée)
     */
    private function getTransactionsData(Carbon $startBound, Carbon $endExclusive, string $selectedOperator): array
    {
        // Implémentation simplifiée - à étendre selon les besoins
        return [
            "daily_volume" => [],
            "by_category" => []
        ];
    }
    
    /**
     * Données d'abonnements (implémentation simplifiée)
     */
    private function getSubscriptionsData(Carbon $startBound, Carbon $endExclusive, string $selectedOperator): array
    {
        // Implémentation simplifiée - à étendre selon les besoins
        return [
            "daily_activations" => [],
            "retention_trend" => []
        ];
    }
}


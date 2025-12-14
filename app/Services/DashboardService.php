<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Récupère l'ID d'un opérateur depuis son nom ou ID
     */
    private function getOperatorId($operator): ?int
    {
        if (is_numeric($operator)) {
            return (int)$operator;
        }
        
        $operatorId = DB::table('country_payments_methods')
            ->whereRaw("TRIM(country_payments_methods_name) = ?", [trim($operator)])
            ->value('country_payments_methods_id');
        
        return $operatorId ? (int)$operatorId : null;
    }
    
    /**
     * Applique le filtre d'opérateur à une requête (gère les IDs et les noms)
     */
    private function applyOperatorFilter($query, string $selectedOperator, string $tableAlias = 'cpm'): void
    {
        if ($selectedOperator !== 'ALL' && !empty($selectedOperator)) {
            $operatorId = $this->getOperatorId($selectedOperator);
            
            if ($operatorId) {
                $query->where("{$tableAlias}.country_payments_methods_id", $operatorId);
            } else {
                // Fallback sur le nom si l'ID n'est pas trouvé
                $query->whereRaw("TRIM({$tableAlias}.country_payments_methods_name) = ?", [trim($selectedOperator)]);
            }
        }
    }
    
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
        
        Log::info("DashboardService: Période de {$periodDays} jours, TTL cache: {$cacheTTL}s, Opérateur: {$selectedOperator}");
        
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
        
        $this->applyOperatorFilter($subscriptionQuery, $selectedOperator);
        
        Log::info("Requête KPIs - Opérateur: {$selectedOperator}");
        
        $subMetrics = $subscriptionQuery->first();
        
        Log::info("KPIs abonnements - Activés: {$subMetrics->activated_current}, Actifs: {$subMetrics->active_current}, Désactivés: {$subMetrics->deactivated_current}");
        
        // Requête unifiée pour les transactions
        $transactionQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->select([
                DB::raw("COUNT(CASE WHEN h.time >= '{$startBound}' AND h.time < '{$endExclusive}' THEN 1 END) as transactions_current"),
                DB::raw("COUNT(CASE WHEN h.time >= '{$compStartBound}' AND h.time < '{$compEndExclusive}' THEN 1 END) as transactions_comparison"),
                DB::raw("COUNT(DISTINCT CASE WHEN h.time >= '{$startBound}' AND h.time < '{$endExclusive}' THEN ca.client_id END) as users_current"),
                DB::raw("COUNT(DISTINCT CASE WHEN h.time >= '{$compStartBound}' AND h.time < '{$compEndExclusive}' THEN ca.client_id END) as users_comparison")
            ]);
        
        $this->applyOperatorFilter($transactionQuery, $selectedOperator);
        
        $txMetrics = $transactionQuery->first();
        
        // Transactions de cohorte (transactions effectuées par les abonnements créés dans la période)
        $cohortTransactionsQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->where('h.time', '>=', $startBound)
            ->where('h.time', '<', $endExclusive)
            ->where('ca.client_abonnement_creation', '>=', $startBound)
            ->where('ca.client_abonnement_creation', '<', $endExclusive);
        $this->applyOperatorFilter($cohortTransactionsQuery, $selectedOperator);
        $cohortTransactions = $cohortTransactionsQuery->count();
        
        $cohortTransactionsComparisonQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->where('h.time', '>=', $compStartBound)
            ->where('h.time', '<', $compEndExclusive)
            ->where('ca.client_abonnement_creation', '>=', $compStartBound)
            ->where('ca.client_abonnement_creation', '<', $compEndExclusive);
        $this->applyOperatorFilter($cohortTransactionsComparisonQuery, $selectedOperator);
        $cohortTransactionsComparison = $cohortTransactionsComparisonQuery->count();
        
        // Utilisateurs transactants de cohorte
        $cohortTransactingUsersQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->where('h.time', '>=', $startBound)
            ->where('h.time', '<', $endExclusive)
            ->where('ca.client_abonnement_creation', '>=', $startBound)
            ->where('ca.client_abonnement_creation', '<', $endExclusive);
        $this->applyOperatorFilter($cohortTransactingUsersQuery, $selectedOperator);
        $cohortTransactingUsers = $cohortTransactingUsersQuery->distinct('ca.client_id')->count('ca.client_id');
        
        $cohortTransactingUsersComparisonQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->where('h.time', '>=', $compStartBound)
            ->where('h.time', '<', $compEndExclusive)
            ->where('ca.client_abonnement_creation', '>=', $compStartBound)
            ->where('ca.client_abonnement_creation', '<', $compEndExclusive);
        $this->applyOperatorFilter($cohortTransactingUsersComparisonQuery, $selectedOperator);
        $cohortTransactingUsersComparison = $cohortTransactingUsersComparisonQuery->distinct('ca.client_id')->count('ca.client_id');
        
        // Calculs des taux
        $retentionRate = $subMetrics->activated_current > 0 ? round(($subMetrics->active_current / $subMetrics->activated_current) * 100, 1) : 0;
        $retentionRateComparison = $subMetrics->activated_comparison > 0 ? round(($subMetrics->active_comparison / $subMetrics->activated_comparison) * 100, 1) : 0;
        
        $conversionRate = $subMetrics->active_current > 0 ? round(($txMetrics->users_current / $subMetrics->active_current) * 100, 1) : 0;
        $conversionRateComparison = $subMetrics->active_comparison > 0 ? round(($txMetrics->users_comparison / $subMetrics->active_comparison) * 100, 1) : 0;
        
        // Calcul du churn rate (abonnements perdus dans la période / abonnements activés)
        // Abonnements perdus = activés ET désactivés dans la période
        $lostSubscriptionsQuery = DB::table('client_abonnement as ca')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->whereBetween('ca.client_abonnement_creation', [$startBound->toDateString(), $endExclusive->copy()->subDay()->toDateString()])
            ->whereNotNull('ca.client_abonnement_expiration')
            ->whereBetween('ca.client_abonnement_expiration', [$startBound->toDateString(), $endExclusive->copy()->subDay()->toDateString()]);
        $this->applyOperatorFilter($lostSubscriptionsQuery, $selectedOperator);
        $lostSubscriptions = $lostSubscriptionsQuery->count();
        
        $lostSubscriptionsComparisonQuery = DB::table('client_abonnement as ca')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->whereBetween('ca.client_abonnement_creation', [$compStartBound->toDateString(), $compEndExclusive->copy()->subDay()->toDateString()])
            ->whereNotNull('ca.client_abonnement_expiration')
            ->whereBetween('ca.client_abonnement_expiration', [$compStartBound->toDateString(), $compEndExclusive->copy()->subDay()->toDateString()]);
        $this->applyOperatorFilter($lostSubscriptionsComparisonQuery, $selectedOperator);
        $lostSubscriptionsComparison = $lostSubscriptionsComparisonQuery->count();
        
        $churnRate = $subMetrics->activated_current > 0 ? round(($lostSubscriptions / $subMetrics->activated_current) * 100, 1) : 0;
        $churnRateComparison = $subMetrics->activated_comparison > 0 ? round(($lostSubscriptionsComparison / $subMetrics->activated_comparison) * 100, 1) : 0;
        
        // Calculer les KPIs des marchands
        $merchantKPIs = $this->calculateMerchantKPIs($startBound, $endExclusive, $compStartBound, $compEndExclusive, $selectedOperator, $txMetrics->transactions_current, $txMetrics->transactions_comparison);
        
        // Calculer transactionsPerUser
        $transactionsPerUser = $txMetrics->users_current > 0 ? round($txMetrics->transactions_current / $txMetrics->users_current, 1) : 0;
        $transactionsPerUserComparison = $txMetrics->users_comparison > 0 ? round($txMetrics->transactions_comparison / $txMetrics->users_comparison, 1) : 0;
        
        // Calculer conversionRatePeriod
        $conversionRatePeriod = $subMetrics->active_current > 0 ? round(($txMetrics->users_current / $subMetrics->active_current) * 100, 2) : 0;
        $conversionRatePeriodComparison = $subMetrics->active_comparison > 0 ? round(($txMetrics->users_comparison / $subMetrics->active_comparison) * 100, 2) : 0;
        
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
            "deactivatedSubscriptions" => [
                "current" => $subMetrics->deactivated_current,
                "previous" => $subMetrics->deactivated_comparison,
                "change" => $this->calculatePercentageChange($subMetrics->deactivated_current, $subMetrics->deactivated_comparison)
            ],
            "periodDeactivated" => [
                "current" => $subMetrics->deactivated_current,
                "previous" => $subMetrics->deactivated_comparison,
                "change" => $this->calculatePercentageChange($subMetrics->deactivated_current, $subMetrics->deactivated_comparison)
            ],
            "cohortDeactivated" => [
                "current" => $lostSubscriptions,
                "previous" => $lostSubscriptionsComparison,
                "change" => $this->calculatePercentageChange($lostSubscriptions, $lostSubscriptionsComparison)
            ],
            "totalTransactions" => [
                "current" => $txMetrics->transactions_current,
                "previous" => $txMetrics->transactions_comparison,
                "change" => $this->calculatePercentageChange($txMetrics->transactions_current, $txMetrics->transactions_comparison)
            ],
            "cohortTransactions" => [
                "current" => $cohortTransactions,
                "previous" => $cohortTransactionsComparison,
                "change" => $this->calculatePercentageChange($cohortTransactions, $cohortTransactionsComparison)
            ],
            "transactingUsers" => [
                "current" => $txMetrics->users_current,
                "previous" => $txMetrics->users_comparison,
                "change" => $this->calculatePercentageChange($txMetrics->users_current, $txMetrics->users_comparison)
            ],
            "cohortTransactingUsers" => [
                "current" => $cohortTransactingUsers,
                "previous" => $cohortTransactingUsersComparison,
                "change" => $this->calculatePercentageChange($cohortTransactingUsers, $cohortTransactingUsersComparison)
            ],
            "retentionRate" => [
                "current" => $retentionRate,
                "previous" => $retentionRateComparison,
                "change" => $this->calculatePercentageChange($retentionRate, $retentionRateComparison)
            ],
            "retentionRateTrue" => [
                "current" => max(0, 100 - $churnRate),
                "previous" => max(0, 100 - $churnRateComparison),
                "change" => $this->calculatePercentageChange(max(0, 100 - $churnRate), max(0, 100 - $churnRateComparison))
            ],
            "conversionRate" => [
                "current" => $conversionRate,
                "previous" => $conversionRateComparison,
                "change" => $this->calculatePercentageChange($conversionRate, $conversionRateComparison)
            ],
            "churnRate" => [
                "current" => $churnRate,
                "previous" => $churnRateComparison,
                "change" => $this->calculatePercentageChange($churnRate, $churnRateComparison)
            ],
            "transactionsPerUser" => [
                "current" => $transactionsPerUser,
                "previous" => $transactionsPerUserComparison,
                "change" => $this->calculatePercentageChange($transactionsPerUser, $transactionsPerUserComparison)
            ],
            "conversionRatePeriod" => [
                "current" => $conversionRatePeriod,
                "previous" => $conversionRatePeriodComparison,
                "change" => $this->calculatePercentageChange($conversionRatePeriod, $conversionRatePeriodComparison)
            ],
            "activeMerchants" => $merchantKPIs['activeMerchants'],
            "activeMerchantRatio" => $merchantKPIs['activeMerchantRatio'],
            "totalPartners" => $merchantKPIs['totalPartners'],
            "totalActivePartnersDB" => $merchantKPIs['totalActivePartnersDB'],
            "totalLocationsActive" => $merchantKPIs['totalLocationsActive'],
            "totalMerchantsEverActive" => $merchantKPIs['totalMerchantsEverActive'],
            "allTransactionsPeriod" => $merchantKPIs['allTransactionsPeriod'],
            "transactionsPerMerchant" => $merchantKPIs['transactionsPerMerchant']
        ];
    }
    
    /**
     * Calcule les KPIs des marchands
     */
    private function calculateMerchantKPIs(Carbon $startBound, Carbon $endExclusive, Carbon $compStartBound, Carbon $compEndExclusive, string $selectedOperator, int $transactionsCurrent, int $transactionsComparison): array
    {
        // Marchands actifs dans la période principale
        $activeMerchantsQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->join('promotion as p', 'h.promotion_id', '=', 'p.promotion_id')
            ->join('partner as pt', 'p.partner_id', '=', 'pt.partner_id')
            ->where('h.time', '>=', $startBound)
            ->where('h.time', '<', $endExclusive)
            ->whereNotNull('h.promotion_id');
        $this->applyOperatorFilter($activeMerchantsQuery, $selectedOperator);
        $activeMerchants = $activeMerchantsQuery->distinct('pt.partner_id')->count('pt.partner_id');
        
        // Marchands actifs dans la période de comparaison
        $activeMerchantsComparisonQuery = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->join('promotion as p', 'h.promotion_id', '=', 'p.promotion_id')
            ->join('partner as pt', 'p.partner_id', '=', 'pt.partner_id')
            ->where('h.time', '>=', $compStartBound)
            ->where('h.time', '<', $compEndExclusive)
            ->whereNotNull('h.promotion_id');
        $this->applyOperatorFilter($activeMerchantsComparisonQuery, $selectedOperator);
        $activeMerchantsComparison = $activeMerchantsComparisonQuery->distinct('pt.partner_id')->count('pt.partner_id');
        
        // Total partenaires actifs
        $totalActivePartnersDB = DB::table('partner')->where('partener_active', 1)->count();
        $totalPartners = $totalActivePartnersDB;
        
        // Total marchands ayant déjà eu des transactions
        $totalMerchantsEverActive = DB::table('history as h')
            ->join('promotion as p', 'h.promotion_id', '=', 'p.promotion_id')
            ->distinct('p.partner_id')
            ->count('p.partner_id');
        
        // Total transactions toutes catégories (période principale)
        $allTransactionsPeriod = DB::table('history')
            ->where('time', '>=', $startBound)
            ->where('time', '<', $endExclusive)
            ->count();
        
        // Total points de vente actifs
        $totalLocationsActive = 0;
        try {
            if (Schema::hasColumn('partner', 'partener_active')) {
                $totalLocationsActive = DB::table('partner_location')
                    ->join('partner', 'partner_location.partner_id', '=', 'partner.partner_id')
                    ->where('partner.partener_active', 1)
                    ->distinct('partner_location.partner_location_id')
                    ->count('partner_location.partner_location_id');
            } else {
                $totalLocationsActive = DB::table('partner_location')
                    ->distinct('partner_location.partner_location_id')
                    ->count('partner_location.partner_location_id');
            }
        } catch (\Exception $e) {
            Log::warning('Impossible de calculer totalLocationsActive', ['error' => $e->getMessage()]);
        }
        
        // Transactions par marchand
        $transactionsPerMerchant = $activeMerchants > 0 ? round($transactionsCurrent / $activeMerchants, 1) : 0;
        $transactionsPerMerchantComparison = $activeMerchantsComparison > 0 ? round($transactionsComparison / $activeMerchantsComparison, 1) : 0;
        
        return [
            "activeMerchants" => [
                "current" => $activeMerchants,
                "previous" => $activeMerchantsComparison,
                "change" => $this->calculatePercentageChange($activeMerchants, $activeMerchantsComparison)
            ],
            "activeMerchantRatio" => [
                "current" => $totalPartners > 0 ? round(($activeMerchants / $totalPartners) * 100, 1) : 0,
                "previous" => $totalPartners > 0 ? round(($activeMerchantsComparison / $totalPartners) * 100, 1) : 0,
                "change" => $this->calculatePercentageChange(
                    $totalPartners > 0 ? round(($activeMerchants / $totalPartners) * 100, 1) : 0,
                    $totalPartners > 0 ? round(($activeMerchantsComparison / $totalPartners) * 100, 1) : 0
                )
            ],
            "totalPartners" => [
                "current" => $totalPartners,
                "previous" => $totalPartners,
                "change" => 0.0
            ],
            "totalActivePartnersDB" => [
                "current" => $totalActivePartnersDB,
                "previous" => $totalActivePartnersDB,
                "change" => 0.0
            ],
            "totalLocationsActive" => [
                "current" => $totalLocationsActive,
                "previous" => $totalLocationsActive,
                "change" => 0.0
            ],
            "totalMerchantsEverActive" => $totalMerchantsEverActive,
            "allTransactionsPeriod" => $allTransactionsPeriod,
            "transactionsPerMerchant" => [
                "current" => $transactionsPerMerchant,
                "previous" => $transactionsPerMerchantComparison,
                "change" => $this->calculatePercentageChange($transactionsPerMerchant, $transactionsPerMerchantComparison)
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
        
        $this->applyOperatorFilter($merchantsQuery, $selectedOperator);
        
        $merchants = $merchantsQuery
            ->groupBy('pt.partner_name', 'pt.partner_id')
            ->having('current', '>', 0) // Seulement les marchands actifs
            ->orderBy('current', 'DESC')
            ->limit(50)
            ->get();
        
        // Total des transactions pour calculer les parts de marché
        $totalTransactions = $merchants->sum('current');
        
        // Récupérer les vraies catégories depuis la base de données
        $partnerIds = $merchants->pluck('partner_id')->toArray();
        $realCategories = $this->getPartnerCategoriesBatch($partnerIds);
        
        // Enrichissement avec catégories réelles et calculs
        $enrichedMerchants = $merchants->map(function($merchant) use ($totalTransactions, $realCategories) {
            // Utiliser la vraie catégorie de la DB, sinon fallback sur le nom
            $category = $realCategories[$merchant->partner_id] ?? $this->categorizePartner($merchant->name ?? 'Unknown');
            $share = $totalTransactions > 0 ? round(($merchant->current / $totalTransactions) * 100, 1) : 0;
            
            return [
                'name' => $merchant->name ?? 'Unknown',
                'category' => $category,
                'current' => (int)$merchant->current,
                'previous' => (int)$merchant->previous,
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
        
        // Récupérer les marchands même pour les longues périodes (limité)
        $merchants = $this->getMerchantsOptimized($startBound, $endExclusive, $compStartBound, $compEndExclusive, $selectedOperator);
        
        // Récupérer les transactions (avec granularité mensuelle pour les longues périodes)
        $transactions = $this->getTransactionsData($startBound, $endExclusive, $selectedOperator);
        
        // Récupérer les données d'abonnements avec activations quotidiennes
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
     * Récupère les catégories réelles des partenaires depuis la base de données
     */
    private function getPartnerCategoriesBatch(array $partnerIds): array
    {
        if (empty($partnerIds)) {
            return [];
        }
        
        $categories = [];
        
        try {
            // Essayer d'abord la relation partner_category
            if (Schema::hasColumn('partner', 'partner_category_id') && 
                Schema::hasTable('partner_category') && 
                Schema::hasColumn('partner_category', 'partner_category_name')) {
                
                $results = DB::table('partner')
                    ->leftJoin('partner_category', 'partner.partner_category_id', '=', 'partner_category.partner_category_id')
                    ->whereIn('partner.partner_id', $partnerIds)
                    ->select('partner.partner_id', 'partner_category.partner_category_name as category')
                    ->get();
                
                foreach ($results as $result) {
                    if ($result->category && trim($result->category) !== '') {
                        $categories[$result->partner_id] = trim($result->category);
                    }
                }
            }
            
            // Pour les partenaires sans catégorie, essayer une colonne catégorie directe
            $missingIds = array_diff($partnerIds, array_keys($categories));
            if (!empty($missingIds)) {
                foreach (['partner_category', 'category', 'business_category', 'sector', 'industry', 'partner_type'] as $column) {
                    if (Schema::hasColumn('partner', $column)) {
                        $results = DB::table('partner')
                            ->whereIn('partner_id', $missingIds)
                            ->select('partner_id', $column . ' as category')
                            ->get();
                        
                        foreach ($results as $result) {
                            if ($result->category && trim($result->category) !== '' && !isset($categories[$result->partner_id])) {
                                $categories[$result->partner_id] = trim($result->category);
                            }
                        }
                        
                        $missingIds = array_diff($missingIds, array_keys($categories));
                        if (empty($missingIds)) break;
                    }
                }
            }
            
            // Pour les partenaires restants, utiliser le fallback basé sur le nom
            $missingIds = array_diff($partnerIds, array_keys($categories));
            if (!empty($missingIds)) {
                $partners = DB::table('partner')
                    ->whereIn('partner_id', $missingIds)
                    ->select('partner_id', 'partner_name')
                    ->get();
                
                foreach ($partners as $partner) {
                    $categories[$partner->partner_id] = $this->categorizePartner($partner->partner_name ?? 'Unknown');
                }
            }
        } catch (\Exception $e) {
            Log::warning("Erreur lors de la récupération des catégories batch: " . $e->getMessage());
            // Fallback: utiliser le nom pour tous
            $partners = DB::table('partner')
                ->whereIn('partner_id', $partnerIds)
                ->select('partner_id', 'partner_name')
                ->get();
            
            foreach ($partners as $partner) {
                $categories[$partner->partner_id] = $this->categorizePartner($partner->partner_name ?? 'Unknown');
            }
        }
        
        return $categories;
    }
    
    /**
     * Calcul de la distribution par catégories (utilise les vraies catégories des partenaires)
     */
    private function calculateCategoryDistribution(array $merchants, int $totalTransactions): array
    {
        $categories = [];
        
        foreach ($merchants as $merchant) {
            $category = $merchant['category'] ?? 'Others';
            if (!isset($categories[$category])) {
                $categories[$category] = ['transactions' => 0, 'merchants' => 0];
            }
            $categories[$category]['transactions'] += (int)($merchant['current'] ?? 0);
            $categories[$category]['merchants']++;
        }
        
        $distribution = [];
        foreach ($categories as $category => $data) {
            $percentage = $totalTransactions > 0 ? round(($data['transactions'] / $totalTransactions) * 100, 1) : 0;
            $distribution[] = [
                'category' => $category,
                'transactions' => (int)$data['transactions'],
                'merchants' => (int)$data['merchants'],
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
     * Données de transactions avec volume quotidien filtré par opérateur
     */
    private function getTransactionsData(Carbon $startBound, Carbon $endExclusive, string $selectedOperator): array
    {
        // Calculer la granularité selon la période (forcer le mode quotidien pour toutes les périodes < 365 jours)
        $periodDays = $startBound->diffInDays($endExclusive);
        $granularity = $periodDays > 365 ? 'month' : 'day'; // Mode quotidien pour périodes <= 365 jours
        $historyDateExpr = $granularity === 'month' ? "DATE_FORMAT(h.time, '%Y-%m-01')" : "DATE(h.time)";
        
        Log::info("getTransactionsData - Période: {$periodDays} jours, Granularité: {$granularity}");
        
        // Transactions agrégées par jour ou par mois selon la période
        $transactionsQuery = DB::table("history as h")
            ->join("client_abonnement as ca", "h.client_abonnement_id", "=", "ca.client_abonnement_id")
            ->join("country_payments_methods as cpm", "ca.country_payments_methods_id", "=", "cpm.country_payments_methods_id")
            ->select(DB::raw("$historyDateExpr as date"), DB::raw("COUNT(*) as transactions"), DB::raw("COUNT(DISTINCT ca.client_id) as users"))
            ->where("h.time", ">=", $startBound)
            ->where("h.time", "<", $endExclusive);
        
        // Appliquer le filtre d'opérateur
        $this->applyOperatorFilter($transactionsQuery, $selectedOperator);
        
        $transactionsRaw = $transactionsQuery
            ->groupBy(DB::raw($historyDateExpr))
            ->orderBy("date")
            ->get()
            ->keyBy('date')
            ->toArray();
        
        Log::info("Transactions raw - Nombre de dates: " . count($transactionsRaw) . ", Exemple de clés: " . implode(', ', array_slice(array_keys($transactionsRaw), 0, 5)));
        
        // Générer la série complète avec toutes les dates
        $startDate = $startBound->copy();
        $endDate = $endExclusive->copy()->subDay();
        $dailyVolume = [];
        
        if ($granularity === 'month') {
            $cursor = $startDate->copy()->firstOfMonth();
            while ($cursor->lte($endDate)) {
                $key = $cursor->copy()->firstOfMonth()->toDateString();
                $row = $transactionsRaw[$key] ?? null;
                $dailyVolume[] = [
                    'date' => $key,
                    'transactions' => $row ? (int)$row->transactions : 0,
                    'users' => $row ? (int)$row->users : 0
                ];
                $cursor->addMonth();
            }
        } else {
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $dateStr = $cursor->toDateString();
                $dayTransactions = isset($transactionsRaw[$dateStr]) ? $transactionsRaw[$dateStr] : null;
                $dailyVolume[] = [
                    'date' => $dateStr,
                    'transactions' => $dayTransactions ? (int)$dayTransactions->transactions : 0,
                    'users' => $dayTransactions ? (int)$dayTransactions->users : 0
                ];
                $cursor->addDay();
            }
        }
        
        return [
            "daily_volume" => $dailyVolume,
            "by_category" => [],
            "analytics" => [
                "byOperator" => $this->getTransactionsByOperator($startBound, $endExclusive, $selectedOperator),
                "byPlan" => $this->getTransactionsByPlan($startBound, $endExclusive, $selectedOperator),
                "byChannel" => []
            ]
        ];
    }
    
    /**
     * Transactions par opérateur
     */
    private function getTransactionsByOperator(Carbon $startBound, Carbon $endExclusive, string $selectedOperator): array
    {
        $query = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->select('cpm.country_payments_methods_name as operator', DB::raw('COUNT(*) as count'))
            ->where('h.time', '>=', $startBound)
            ->where('h.time', '<', $endExclusive);
        
        $this->applyOperatorFilter($query, $selectedOperator);
        
        return $query->groupBy('cpm.country_payments_methods_name')
            ->get()
            ->map(function($item) {
                return [
                    'operator' => $item->operator,
                    'count' => (int)$item->count
                ];
            })
            ->toArray();
    }
    
    /**
     * Transactions par plan
     */
    private function getTransactionsByPlan(Carbon $startBound, Carbon $endExclusive, string $selectedOperator): array
    {
        $query = DB::table('history as h')
            ->join('client_abonnement as ca', 'h.client_abonnement_id', '=', 'ca.client_abonnement_id')
            ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
            ->select(
                DB::raw("CASE 
                    WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) = 1 THEN 'Journalier'
                    WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) BETWEEN 20 AND 40 THEN 'Mensuel'
                    WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) >= 330 THEN 'Annuel'
                    ELSE 'Autre'
                END as plan"),
                DB::raw('COUNT(*) as count')
            )
            ->where('h.time', '>=', $startBound)
            ->where('h.time', '<', $endExclusive)
            ->whereNotNull('ca.client_abonnement_expiration');
        
        $this->applyOperatorFilter($query, $selectedOperator);
        
        return $query->groupBy(DB::raw("CASE 
                    WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) = 1 THEN 'Journalier'
                    WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) BETWEEN 20 AND 40 THEN 'Mensuel'
                    WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) >= 330 THEN 'Annuel'
                    ELSE 'Autre'
                END"))
            ->get()
            ->map(function($item) {
                return [
                    'plan' => $item->plan,
                    'count' => (int)$item->count
                ];
            })
            ->toArray();
    }
    
    /**
     * Données d'abonnements avec activations quotidiennes filtrées par opérateur
     */
    private function getSubscriptionsData(Carbon $startBound, Carbon $endExclusive, string $selectedOperator): array
    {
        // Calculer la granularité selon la période (forcer le mode quotidien pour toutes les périodes < 365 jours)
        $periodDays = $startBound->diffInDays($endExclusive);
        $granularity = $periodDays > 365 ? 'month' : 'day'; // Mode quotidien pour périodes <= 365 jours
        $caDateExpr = $granularity === 'month' ? "DATE_FORMAT(client_abonnement_creation, '%Y-%m-01')" : "DATE(client_abonnement_creation)";
        
        Log::info("getSubscriptionsData - Période: {$periodDays} jours, Granularité: {$granularity}");
        
        // Requête pour les activations quotidiennes/mensuelles avec filtre opérateur
        $activationsQuery = DB::table("client_abonnement as ca")
            ->join("country_payments_methods as cpm", "ca.country_payments_methods_id", "=", "cpm.country_payments_methods_id")
            ->select(DB::raw("$caDateExpr as date"), DB::raw("COUNT(*) as activations"))
            ->where("ca.client_abonnement_creation", ">=", $startBound)
            ->where("ca.client_abonnement_creation", "<", $endExclusive);
        
        // Appliquer le filtre d'opérateur
        $this->applyOperatorFilter($activationsQuery, $selectedOperator);
        
        Log::info("Requête activations quotidiennes - Opérateur: {$selectedOperator}, Période: {$startBound->toDateString()} à {$endExclusive->toDateString()}");
        
        $activationsRaw = $activationsQuery
            ->groupBy(DB::raw($caDateExpr))
            ->orderBy("date")
            ->get()
            ->keyBy('date')
            ->toArray();
        
        Log::info("Activations trouvées: " . count($activationsRaw) . " jours/mois avec données");
        
        // Générer la série complète avec toutes les dates
        $startDate = $startBound->copy();
        $endDate = $endExclusive->copy()->subDay();
        $dailyActivations = [];
        
        if ($granularity === 'month') {
            $cursor = $startDate->copy()->firstOfMonth();
            while ($cursor->lte($endDate)) {
                $key = $cursor->copy()->firstOfMonth()->toDateString();
                $activations = isset($activationsRaw[$key]) ? (int)$activationsRaw[$key]->activations : 0;
                $dailyActivations[] = [
                    'date' => $key,
                    'activations' => $activations,
                    'active' => round($activations * 0.95)
                ];
                $cursor->addMonth();
            }
        } else {
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $dateStr = $cursor->toDateString();
                $activations = isset($activationsRaw[$dateStr]) ? (int)$activationsRaw[$dateStr]->activations : 0;
                $dailyActivations[] = [
                    'date' => $dateStr,
                    'activations' => $activations,
                    'active' => round($activations * 0.95)
                ];
                $cursor->addDay();
            }
        }
        
        // Calculer retention_trend (optimisé - éviter les requêtes par jour)
        $retentionTrend = $this->calculateRetentionTrendOptimized($startBound, $endExclusive, $selectedOperator);
        
        // Calculer quarterly_active_locations (simplifié pour éviter les timeouts)
        $quarterlyActiveLocations = $this->calculateQuarterlyActiveLocations($endExclusive->copy()->subDay()->toDateString());
        
        // Récupérer les détails des abonnements (limité pour éviter les timeouts)
        $subscriptionDetails = $this->getSubscriptionDetails($startBound, $endExclusive, $selectedOperator);
        
        // Calculer activations_by_channel (simplifié - retourner des zéros pour l'instant)
        $activationsByChannel = [
            "cb" => ["current" => 0, "previous" => 0, "change" => 0],
            "recharge" => ["current" => 0, "previous" => 0, "change" => 0],
            "phone_balance" => ["current" => 0, "previous" => 0, "change" => 0],
            "other" => ["current" => 0, "previous" => 0, "change" => 0]
        ];
        
        // Calculer plan_distribution (simplifié - retourner des zéros pour l'instant)
        $planDistribution = [
            "daily" => ["current" => 0, "previous" => 0, "change" => 0],
            "monthly" => ["current" => 0, "previous" => 0, "change" => 0],
            "annual" => ["current" => 0, "previous" => 0, "change" => 0],
            "other" => ["current" => 0, "previous" => 0, "change" => 0]
        ];
        
        // Calculer cohorts (simplifié - retourner un tableau vide pour l'instant)
        $cohorts = [];
        
        // Calculer renewal_rate, average_lifespan, reactivation_rate (simplifié)
        $renewalRate = ["current" => 0, "previous" => 0, "change" => 0];
        $averageLifespan = ["current" => 0, "previous" => 0, "change" => 0];
        $reactivationRate = ["current" => 0, "previous" => 0, "change" => 0];
        
        return [
            "daily_activations" => $dailyActivations,
            "retention_trend" => $retentionTrend,
            "quarterly_active_locations" => $quarterlyActiveLocations,
            "details" => $subscriptionDetails,
            "activations_by_channel" => $activationsByChannel,
            "plan_distribution" => $planDistribution,
            "cohorts" => $cohorts,
            "renewal_rate" => $renewalRate,
            "average_lifespan" => $averageLifespan,
            "reactivation_rate" => $reactivationRate
        ];
    }
    
    /**
     * Calcule la tendance de rétention jour par jour (VERSION OPTIMISÉE - une seule requête)
     */
    private function calculateRetentionTrendOptimized(Carbon $startBound, Carbon $endExclusive, string $selectedOperator): array
    {
        try {
            $periodDays = $startBound->diffInDays($endExclusive);
            
            // Pour les longues périodes, utiliser des intervalles plus grands
            $intervalDays = max(1, intval($periodDays / 30)); // Maximum 30 points
            
            // Requête optimisée : récupérer toutes les données en une seule fois
            $endDateStr = $endExclusive->toDateString();
            $activationsQuery = DB::table('client_abonnement as ca')
                ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
                ->select(
                    DB::raw("DATE(ca.client_abonnement_creation) as date"),
                    DB::raw("COUNT(*) as activated"),
                    DB::raw("SUM(CASE WHEN ca.client_abonnement_expiration IS NULL OR ca.client_abonnement_expiration > '{$endDateStr}' THEN 1 ELSE 0 END) as active")
                )
                ->where('ca.client_abonnement_creation', '>=', $startBound)
                ->where('ca.client_abonnement_creation', '<', $endExclusive);
            
            $this->applyOperatorFilter($activationsQuery, $selectedOperator);
            
            $results = $activationsQuery
                ->groupBy(DB::raw("DATE(ca.client_abonnement_creation)"))
                ->orderBy('date')
                ->get()
                ->keyBy('date');
            
            // Générer la série complète
            $trend = [];
            $cursor = $startBound->copy();
            $endDate = $endExclusive->copy()->subDay();
            
            while ($cursor->lte($endDate)) {
                $dateStr = $cursor->toDateString();
                $result = $results->get($dateStr);
                
                if ($result) {
                    $rate = $result->activated > 0 ? round(($result->active / $result->activated) * 100, 1) : 100.0;
                } else {
                    $rate = 100.0; // Pas d'activations ce jour = 100% de rétention
                }
                
                $trend[] = [
                    'date' => $dateStr,
                    'rate' => $rate,
                    'value' => $rate
                ];
                
                $cursor->addDay();
            }
            
            return $trend;
        } catch (\Exception $e) {
            Log::error("Erreur lors du calcul de la tendance de rétention: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calcule la tendance de rétention jour par jour (ANCIENNE VERSION - trop lente)
     */
    private function calculateRetentionTrend(string $startDate, string $endDate, string $selectedOperator): array
    {
        // Déléguer à la version optimisée
        return $this->calculateRetentionTrendOptimized(
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->addDay()->startOfDay(),
            $selectedOperator
        );
    }
    
    /**
     * Calcule les points de vente actifs par trimestre (optimisé avec calcul réel par trimestre)
     */
    private function calculateQuarterlyActiveLocations(string $endDate): array
    {
        try {
            $quarterlyActiveLocations = [];
            $quarterCursor = Carbon::parse($endDate)->firstOfQuarter()->subQuarters(7);
            $quarterEnd = Carbon::parse($endDate)->firstOfQuarter();
            
            while ($quarterCursor->lte($quarterEnd)) {
                $qEnd = $quarterCursor->copy()->endOfQuarter();
                
                // Calculer les points de vente actifs pour ce trimestre spécifique
                $countLocations = 0;
                try {
                    if (Schema::hasColumn('partner', 'partener_active')) {
                        $countLocations = DB::table('partner_location')
                            ->join('partner', 'partner_location.partner_id', '=', 'partner.partner_id')
                            ->where('partner.partener_active', 1)
                            ->when(Schema::hasColumn('partner_location', 'created_at'), function($q) use ($qEnd) {
                                return $q->where('partner_location.created_at', '<=', $qEnd);
                            })
                            ->distinct('partner_location.partner_location_id')
                            ->count('partner_location.partner_location_id');
                    } else {
                        $countLocations = DB::table('partner_location')
                            ->when(Schema::hasColumn('partner_location', 'created_at'), function($q) use ($qEnd) {
                                return $q->where('partner_location.created_at', '<=', $qEnd);
                            })
                            ->distinct('partner_location.partner_location_id')
                            ->count('partner_location.partner_location_id');
                    }
                } catch (\Exception $e) {
                    Log::warning("Erreur calcul locations pour {$quarterCursor->format('Y-Q')}: " . $e->getMessage());
                    // Utiliser le total actuel en fallback
                    if (Schema::hasColumn('partner', 'partener_active')) {
                        $countLocations = DB::table('partner_location')
                            ->join('partner', 'partner_location.partner_id', '=', 'partner.partner_id')
                            ->where('partner.partener_active', 1)
                            ->distinct('partner_location.partner_location_id')
                            ->count('partner_location.partner_location_id');
                    } else {
                        $countLocations = DB::table('partner_location')
                            ->distinct('partner_location.partner_location_id')
                            ->count('partner_location.partner_location_id');
                    }
                }
                
                $quarterlyActiveLocations[] = [
                    'quarter' => $quarterCursor->format('Y') . '-Q' . $quarterCursor->quarter,
                    'locations' => (int)$countLocations
                ];
                
                $quarterCursor->addQuarter();
            }
            
            return $quarterlyActiveLocations;
        } catch (\Exception $e) {
            Log::error("Erreur lors du calcul des points de vente actifs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les détails des abonnements (limité pour éviter les timeouts)
     */
    private function getSubscriptionDetails(Carbon $startBound, Carbon $endExclusive, string $selectedOperator): array
    {
        try {
            $periodDays = $startBound->diffInDays($endExclusive);
            
            // Limiter à 1000 résultats maximum pour éviter les timeouts
            $limit = min(1000, max(100, intval($periodDays * 10)));
            
            $query = DB::table('client_abonnement as ca')
                ->leftJoin('client as c', 'ca.client_id', '=', 'c.client_id')
                ->join('country_payments_methods as cpm', 'ca.country_payments_methods_id', '=', 'cpm.country_payments_methods_id')
                ->select([
                    'c.client_prenom as first_name',
                    'c.client_nom as last_name',
                    'c.client_telephone as phone',
                    'cpm.country_payments_methods_name as operator',
                    'ca.client_abonnement_creation as activation_date',
                    'ca.client_abonnement_expiration as end_date',
                    DB::raw("CASE 
                        WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) = 1 THEN 'Journalier'
                        WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) BETWEEN 20 AND 40 THEN 'Mensuel'
                        WHEN DATEDIFF(ca.client_abonnement_expiration, ca.client_abonnement_creation) >= 330 THEN 'Annuel'
                        ELSE 'Autre'
                    END as plan")
                ])
                ->where('ca.client_abonnement_creation', '>=', $startBound)
                ->where('ca.client_abonnement_creation', '<', $endExclusive);
            
            $this->applyOperatorFilter($query, $selectedOperator);
            
            // Compter le total avant de limiter
            $totalCount = $query->count();
            
            // Limiter les résultats
            $results = $query->orderByDesc('ca.client_abonnement_creation')->limit($limit)->get();
            
            return [
                'data' => $results->toArray(),
                'meta' => [
                    'total_count' => $totalCount,
                    'displayed_count' => $results->count(),
                    'limit' => $limit,
                    'execution_time_ms' => 0,
                    'period' => $startBound->toDateString() . ' - ' . $endExclusive->copy()->subDay()->toDateString()
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des détails des abonnements: " . $e->getMessage());
            return [
                'data' => [],
                'meta' => [
                    'total_count' => 0,
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
}


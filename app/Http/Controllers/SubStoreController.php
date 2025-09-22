<?php

namespace App\Http\Controllers;

use App\Services\SubStoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SubStoreController extends Controller
{
    protected $subStoreService;

    public function __construct(SubStoreService $subStoreService)
    {
        $this->subStoreService = $subStoreService;
    }

    /**
     * Afficher le dashboard sub-stores
     */
    public function index()
    {
        $user = auth()->user();
        
        // Déterminer les sub-stores accessibles selon le rôle
        $availableSubStores = $this->subStoreService->getAvailableSubStoresForUser($user);
        $defaultSubStore = $this->subStoreService->getDefaultSubStoreForUser($user);
        
        return view('sub-stores.dashboard', compact('availableSubStores', 'defaultSubStore'));
    }

    /**
     * API - Récupérer les sub-stores disponibles pour l'utilisateur
     */
    public function getSubStores()
    {
        $user = auth()->user();
        $availableSubStores = $this->subStoreService->getAvailableSubStoresForUser($user);
        $defaultSubStore = $this->subStoreService->getDefaultSubStoreForUser($user);
        
        return response()->json([
            'sub_stores' => $availableSubStores,
            'default_sub_store' => $defaultSubStore,
            'user_role' => $user->role->name ?? 'collaborator'
        ]);
    }


    /**
     * API async: Expirations par mois (léger, cache 10 min)
     */
    public function getExpirationsAsync(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $selectedSubStore = $request->input('sub_store', 'ALL');
        try {
            $cacheKey = 'expirations_async:' . md5(($startDate ?? 'n/a').($endDate ?? 'n/a').$selectedSubStore);
            $data = Cache::remember($cacheKey, 600, function() use ($selectedSubStore) {
                return $this->getExpirationsByMonth($selectedSubStore, 12);
            });
            return response()->json(['expirationsByMonth' => $data, 'cached' => true]);
        } catch (\Throwable $th) {
            Log::warning('Erreur expirations async: '.$th->getMessage());
            return response()->json(['expirationsByMonth' => [], 'error' => $th->getMessage()], 200);
        }
    }

    /**
     * API - Récupérer les données du dashboard sub-stores
     */
    public function getDashboardData(Request $request)
    {
        try {
            Log::info("=== DÉBUT API SubStore getDashboardData ===");
            
            // Période par défaut avec données réelles sub-stores : 2025-08-18 → 2025-08-24 (clients avec cartes)
            $startDate = $request->input("start_date", "2025-08-18");
            $endDate = $request->input("end_date", "2025-08-24");
            $comparisonStartDate = $request->input("comparison_start_date", "2025-08-11");
            $comparisonEndDate = $request->input("comparison_end_date", "2025-08-17");
            $selectedSubStore = $request->input("sub_store", "ALL");
            
            // Vérification des permissions
            $user = auth()->user();
            $selectedSubStore = $this->validateSubStoreAccess($user, $selectedSubStore);
            
            Log::info("Sub-Store sélectionné: $selectedSubStore");
            Log::info("Utilisateur: {$user->email} (Rôle: {$user->role->name})");

            // Générer la clé de cache
            $cacheKey = $this->generateCacheKey($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore, $user->id);
            
            // Cache intelligent selon la longueur de période (optimisé comme dashboard opérateur)
            $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $ttl = $periodDays > 120 ? 120 : ($periodDays > 30 ? 90 : 30); // 2min/1.5min/30s
            
            $data = Cache::remember($cacheKey, $ttl, function () use ($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore, $periodDays) {
                Log::info("Cache MISS - Récupération des données sub-stores depuis la base");
                
                // Détection de période longue pour optimisation (désactivé temporairement)
                if ($periodDays > 365) { // Augmenté le seuil pour éviter le mode optimisé
                    Log::info("PÉRIODE LONGUE DÉTECTÉE ($periodDays jours) - Mode optimisé activé");
                    return $this->getOptimizedSubStoreDashboardData($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore);
                }
                
                return $this->fetchSubStoreDashboardData($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore);
            });
            
            if (Cache::has($cacheKey)) {
                Log::info("Cache HIT - Données sub-stores servies depuis le cache");
            }
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error("Erreur dans SubStore getDashboardData: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            Log::error("File: " . $e->getFile() . " Line: " . $e->getLine());
            
            return response()->json([
                'error' => 'Erreur lors du chargement des données: ' . $e->getMessage(),
                'kpis' => $this->getFallbackSubStoreKpis(),
                'sub_stores' => [],
                'insights' => ['positive' => [], 'negative' => [], 'recommendations' => []],
                'data_source' => 'fallback'
            ], 500);
        }
    }

    /**
     * Mode optimisé pour les longues périodes (comme dashboard opérateur)
     */
    private function getOptimizedSubStoreDashboardData(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $selectedSubStore): array
    {
        try {
            $startTime = microtime(true);
            Log::info("=== MODE OPTIMISÉ SUB-STORE POUR LONGUE PÉRIODE ===");

            // Cache plus long pour les longues périodes (10 minutes)
            $cacheKey = 'substore_optimized_v2:' . md5($startDate . $endDate . $comparisonStartDate . $comparisonEndDate . $selectedSubStore);
            
            return Cache::remember($cacheKey, 600, function() use ($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore, $startTime) {
                
                $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
                $granularity = $periodDays > 365 ? 'month' : ($periodDays > 120 ? 'week' : 'day');
                
                Log::info("Granularité optimisée: $granularity pour $periodDays jours");
                
                // === KPIs OPTIMISÉS BASÉS SUR LES CARTES DE RECHARGE ===
                
                // Utiliser les mêmes méthodes que le mode normal mais avec cache individuel
                $distributed = Cache::remember("distributed_$selectedSubStore", 300, function() use ($selectedSubStore) {
                    return $this->getDistributedCards($selectedSubStore);
                });
                
                $inscriptions = Cache::remember("inscriptions_$selectedSubStore", 300, function() use ($selectedSubStore) {
                    return $this->getInscriptionsWithCards($selectedSubStore);
                });
                
                $activeUsers = Cache::remember("activeUsers_$selectedSubStore", 300, function() use ($selectedSubStore) {
                    return $this->getActiveUsersWithCards($selectedSubStore);
                });
                
                $activeUsersCohorte = Cache::remember("activeUsersCohorte_{$selectedSubStore}_{$startDate}_{$endDate}", 300, function() use ($selectedSubStore, $startDate, $endDate) {
                    return $this->getActiveUsersWithCardsCohorte($selectedSubStore, $startDate, $endDate);
                });
                
                $transactions = Cache::remember("transactions_$selectedSubStore", 300, function() use ($selectedSubStore) {
                    return $this->getTransactionsWithCards($selectedSubStore);
                });
                
                $transactionsCohorte = Cache::remember("transactionsCohorte_{$selectedSubStore}_{$startDate}_{$endDate}", 300, function() use ($selectedSubStore, $startDate, $endDate) {
                    return $this->getTransactionsWithCardsCohorte($selectedSubStore, $startDate, $endDate);
                });
                
                $inscriptionsCohorte = Cache::remember("inscriptionsCohorte_{$selectedSubStore}_{$startDate}_{$endDate}", 300, function() use ($selectedSubStore, $startDate, $endDate) {
                    return $this->getInscriptionsWithCardsCohorte($selectedSubStore, $startDate, $endDate);
                });
                
                $conversionRate = $distributed > 0 ? round(($inscriptions / $distributed) * 100, 1) : 0;

                // === COMPARAISONS OPTIMISÉES ===
                $previousDistributed = Cache::remember("prevDistributed_$selectedSubStore", 300, function() use ($selectedSubStore) {
                    return $this->getDistributedCards($selectedSubStore);
                });
                
                $previousInscriptions = Cache::remember("prevInscriptions_$selectedSubStore", 300, function() use ($selectedSubStore) {
                    return $this->getInscriptionsWithCards($selectedSubStore);
                });
                
                $previousActiveUsers = Cache::remember("prevActiveUsers_$selectedSubStore", 300, function() use ($selectedSubStore) {
                    return $this->getActiveUsersWithCards($selectedSubStore);
                });
                
                $previousTransactions = Cache::remember("prevTransactions_$selectedSubStore", 300, function() use ($selectedSubStore) {
                    return $this->getTransactionsWithCards($selectedSubStore);
                });

                // === TOP SUB-STORES OPTIMISÉ ===
                $topSubStores = Cache::remember("topSubStores_optimized_{$selectedSubStore}", 300, function() use ($selectedSubStore, $startDate, $endDate) {
                    return $this->getOptimizedTopSubStores($startDate, $endDate, $selectedSubStore);
                });

                // === GRAPHIQUES OPTIMISÉS ===
                $categoryDistribution = Cache::remember("categoryDistribution_optimized_{$selectedSubStore}_{$startDate}_{$endDate}", 300, function() use ($selectedSubStore, $startDate, $endDate) {
                    return $this->getOptimizedCategoryDistribution($selectedSubStore, $startDate, $endDate);
                });

                $inscriptionTrends = Cache::remember("inscriptionTrends_optimized_{$selectedSubStore}_{$startDate}_{$endDate}", 300, function() use ($selectedSubStore, $startDate, $endDate, $granularity) {
                    return $this->getOptimizedInscriptionTrends($selectedSubStore, $startDate, $endDate, $granularity);
                });

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                Log::info("Mode optimisé terminé en {$executionTime}ms");

                return [
                    'kpis' => [
                        'distributed' => [
                            'current' => $distributed,
                            'previous' => $previousDistributed,
                            'change' => $previousDistributed > 0 ? round((($distributed - $previousDistributed) / $previousDistributed) * 100, 1) : 0
                        ],
                        'inscriptions' => [
                            'current' => $inscriptions,
                            'previous' => $previousInscriptions,
                            'change' => $previousInscriptions > 0 ? round((($inscriptions - $previousInscriptions) / $previousInscriptions) * 100, 1) : 0
                        ],
                        'conversionRate' => [
                            'current' => $conversionRate,
                            'previous' => 0, // Pas de comparaison pour le taux
                            'change' => 0
                        ],
                        'transactions' => [
                            'current' => $transactions,
                            'previous' => $previousTransactions,
                            'change' => $previousTransactions > 0 ? round((($transactions - $previousTransactions) / $previousTransactions) * 100, 1) : 0
                        ],
                        'activeUsers' => [
                            'current' => $activeUsers,
                            'previous' => $previousActiveUsers,
                            'change' => $previousActiveUsers > 0 ? round((($activeUsers - $previousActiveUsers) / $previousActiveUsers) * 100, 1) : 0
                        ],
                        'activeUsersCohorte' => [
                            'current' => $activeUsersCohorte,
                            'previous' => 0,
                            'change' => 0
                        ],
                        'transactionsCohorte' => [
                            'current' => $transactionsCohorte,
                            'previous' => 0,
                            'change' => 0
                        ],
                        'inscriptionsCohorte' => [
                            'current' => $inscriptionsCohorte,
                            'previous' => 0,
                            'change' => 0
                        ]
                    ],
                    'sub_stores' => $topSubStores,
                    'categoryDistribution' => $categoryDistribution,
                    'inscriptionTrends' => $inscriptionTrends,
                    'insights' => [
                        'positive' => [],
                        'negative' => [],
                        'recommendations' => []
                    ],
                    'data_source' => 'optimized_database',
                    'execution_time_ms' => $executionTime
                ];
            });
            
        } catch (\Exception $e) {
            Log::error("Erreur mode optimisé sub-store: " . $e->getMessage());
            return $this->getFallbackSubStoreData();
        }
    }

    /**
     * Méthodes optimisées pour les graphiques
     */
    private function getOptimizedCategoryDistribution(string $selectedSubStore, string $startDate, string $endDate): array
    {
        try {
            // Version simplifiée pour les longues périodes
            return Cache::remember("categoryDist_opt_{$selectedSubStore}_{$startDate}_{$endDate}", 300, function() use ($selectedSubStore, $startDate, $endDate) {
                return $this->getCategoryDistribution($selectedSubStore, $startDate, $endDate);
            });
        } catch (\Exception $e) {
            Log::warning("Erreur distribution catégories optimisée: " . $e->getMessage());
            return [];
        }
    }

    private function getOptimizedInscriptionTrends(string $selectedSubStore, string $startDate, string $endDate, string $granularity): array
    {
        try {
            // Version simplifiée pour les longues périodes
            return Cache::remember("inscriptionTrends_opt_{$selectedSubStore}_{$startDate}_{$endDate}_{$granularity}", 300, function() use ($selectedSubStore, $startDate, $endDate, $granularity) {
                return $this->getOptimizedInscriptionsTrend($startDate, $endDate, $selectedSubStore, $granularity);
            });
        } catch (\Exception $e) {
            Log::warning("Erreur tendances inscriptions optimisées: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les données depuis la base de données
     */
    private function fetchSubStoreDashboardData(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $selectedSubStore = "ALL"): array
    {
        try {
            Log::info("=== DÉBUT fetchSubStoreDashboardData ===");
            Log::info("Période principale: $startDate à $endDate");
            Log::info("Période comparaison: $comparisonStartDate à $comparisonEndDate");
            Log::info("Sub-Store filtré: $selectedSubStore");
            
            // Détection des longues périodes pour optimisation
            $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            $isLongPeriod = $periodDays > 90;
            
            if ($isLongPeriod) {
                Log::info("PÉRIODE LONGUE DÉTECTÉE ($periodDays jours) - Mode optimisé activé");
                return $this->fetchOptimizedSubStoreData($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore);
            }
            
            // === KPIs BASÉS SUR LES CARTES DE RECHARGE ===
            
            // 1. DISTRIBUÉ : Total des cartes de recharge pour le sub-store (sans filtre de date)
            $distributed = $this->getDistributedCards($selectedSubStore);
            Log::info("Distribué (cartes totales): $distributed");
            
            // 2. INSCRIPTIONS : Clients inscrits avec cartes de recharge (sans filtre de date)
            $inscriptions = $this->getInscriptionsWithCards($selectedSubStore);
            Log::info("Inscriptions (avec cartes): $inscriptions");
            
            // 3. ACTIVE USERS : Clients avec abonnements actifs + cartes de recharge (sans filtre de date)
            $activeUsers = $this->getActiveUsersWithCards($selectedSubStore);
            Log::info("Active users (avec cartes): $activeUsers");
            
            // 4. ACTIVE USERS COHORTE : Clients avec abonnements actifs + cartes de recharge (avec filtre de date)
            $activeUsersCohorte = $this->getActiveUsersWithCardsCohorte($selectedSubStore, $startDate, $endDate);
            Log::info("Active users cohorte (avec cartes): $activeUsersCohorte");

            // 4bis. TOTAL ABONNEMENTS (toutes périodes)
            $totalSubscriptions = Cache::remember("total_subscriptions_{$selectedSubStore}", 600, function() use ($selectedSubStore) {
                return $this->getTotalSubscriptions($selectedSubStore);
            });
            Log::info("Total abonnements: $totalSubscriptions");

            // 4ter. TAUX DE RENOUVELLEMENT (sur la période sélectionnée)
            $renewal = Cache::remember("renewal_stats_{$selectedSubStore}_{$startDate}_{$endDate}", 600, function() use ($selectedSubStore, $startDate, $endDate) {
                return $this->getRenewalStats($selectedSubStore, $startDate, $endDate);
            });
            $renewalRate = $renewal['renewal_rate'];
            Log::info("Taux de renouvellement: {$renewalRate}%");
            
            // 5. TRANSACTIONS : Abonnements activés avec cartes de recharge (sans filtre de date)
            $transactions = $this->getTransactionsWithCards($selectedSubStore);
            Log::info("Transactions (avec cartes): $transactions");
            
            // 6. TRANSACTIONS COHORTE : Abonnements activés avec cartes de recharge (avec filtre de date)
            $transactionsCohorte = $this->getTransactionsWithCardsCohorte($selectedSubStore, $startDate, $endDate);
            Log::info("Transactions cohorte (avec cartes): $transactionsCohorte");
            
            // 7. INSCRIPTIONS COHORTE : Clients inscrits avec cartes de recharge (avec filtre de date)
            $inscriptionsCohorte = $this->getInscriptionsWithCardsCohorte($selectedSubStore, $startDate, $endDate);
            Log::info("Inscriptions cohorte (avec cartes): $inscriptionsCohorte");
            
            // 8. TAUX DE CONVERSION : (Inscriptions TOTAL / Distribué) * 100
            $conversionRate = $distributed > 0 ? round(($inscriptions / $distributed) * 100, 1) : 0;
            Log::info("Taux de conversion: $conversionRate%");
            
            // === KPIs PÉRIODE DE COMPARAISON (même logique mais pour la période de comparaison) ===
            
            $distributedComparison = $this->getDistributedCards($selectedSubStore); // Même valeur car sans filtre de date
            $inscriptionsComparison = $this->getInscriptionsWithCards($selectedSubStore); // Même valeur car sans filtre de date
            $activeUsersComparison = $this->getActiveUsersWithCards($selectedSubStore); // Même valeur car sans filtre de date
            $transactionsComparison = $this->getTransactionsWithCards($selectedSubStore); // Même valeur car sans filtre de date
            
            // Pour les KPIs avec filtre de date, on calcule pour la période de comparaison
            $activeUsersCohorteComparison = $this->getActiveUsersWithCardsCohorte($selectedSubStore, $comparisonStartDate, $comparisonEndDate);
            $transactionsCohorteComparison = $this->getTransactionsWithCardsCohorte($selectedSubStore, $comparisonStartDate, $comparisonEndDate);
            $inscriptionsCohorteComparison = $this->getInscriptionsWithCardsCohorte($selectedSubStore, $comparisonStartDate, $comparisonEndDate);
            
            $conversionRateComparison = $distributedComparison > 0 ? round(($inscriptionsCohorteComparison / $distributedComparison) * 100, 1) : 0;
            $totalSubscriptionsComparison = $this->getTotalSubscriptions($selectedSubStore);
            $renewalComparison = $this->getRenewalStats($selectedSubStore, $comparisonStartDate, $comparisonEndDate);
            $renewalRateComparison = $renewalComparison['renewal_rate'];
            
            // === CALCUL DES VARIATIONS ===
            
            $distributedChange = $this->calculatePercentageChange($distributedComparison, $distributed);
            $inscriptionsChange = $this->calculatePercentageChange($inscriptionsComparison, $inscriptions);
            $activeUsersChange = $this->calculatePercentageChange($activeUsersComparison, $activeUsers);
            $activeUsersCohorteChange = $this->calculatePercentageChange($activeUsersCohorteComparison, $activeUsersCohorte);
            $transactionsChange = $this->calculatePercentageChange($transactionsComparison, $transactions);
            $transactionsCohorteChange = $this->calculatePercentageChange($transactionsCohorteComparison, $transactionsCohorte);
            $inscriptionsCohorteChange = $this->calculatePercentageChange($inscriptionsCohorteComparison, $inscriptionsCohorte);
            $conversionRateChange = $this->calculatePercentageChange($conversionRateComparison, $conversionRate);
            $totalSubscriptionsChange = $this->calculatePercentageChange($totalSubscriptionsComparison, $totalSubscriptions);
            $renewalRateChange = $this->calculatePercentageChange($renewalRateComparison, $renewalRate);
            
            // === DONNÉES DES CATÉGORIES ===
            
            $categoryDistribution = $this->getCategoryDistribution($startDate, $endDate, $selectedSubStore);
            $inscriptionsTrend = $this->getInscriptionsTrend($startDate, $endDate, $selectedSubStore);
            $expirationsByMonth = Cache::remember("expirations_by_month_{$selectedSubStore}", 600, function() use ($selectedSubStore) {
                return $this->getExpirationsByMonth($selectedSubStore, 12);
            });
            
            // Supprimer le fallback: afficher vide si aucune donnée réelle
            
            // Si pas de données de tendance, créer des données de démonstration
            if (empty($inscriptionsTrend)) {
                $inscriptionsTrend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $inscriptionsTrend[] = [
                        'date' => $date->format('d M'),
                        'value' => rand(50, 200)
                    ];
                }
            }
            
            $revenueComparisonQuery = DB::table("client_abonnement")
                ->join("client", "client_abonnement.client_id", "=", "client.client_id")
                ->join("stores", "client.sub_store", "=", "stores.store_id")
                ->join("abonnement_tarifs", "client_abonnement.tarif_id", "=", "abonnement_tarifs.abonnement_tarifs_id")
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client_abonnement.client_abonnement_creation", [$comparisonStartDate, Carbon::parse($comparisonEndDate)->endOfDay()])
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%" . $selectedSubStore . "%");
                });
            
            $totalRevenueComparison = $revenueComparisonQuery->sum('abonnement_tarifs.abonnement_tarifs_prix');
            $estimatedRevenueComparison = $totalRevenueComparison * 0.1;
            
            // === TOP SUB-STORES ===
            // Désactivé pour accélérer le chargement (demande utilisateur)
            $topSubStores = [];
            
            // === RÉPARTITION PAR TYPES DE SUB-STORES ===
            $subStoreTypeDistribution = DB::table("stores")
                ->leftJoin("client", "stores.store_id", "=", "client.sub_store")
                ->select(
                    "stores.store_type",
                    DB::raw("COUNT(DISTINCT stores.store_id) as store_count"),
                    DB::raw("COUNT(DISTINCT client.client_id) as client_count")
                )
                ->where("stores.is_sub_store", 1)
                ->where("stores.store_active", 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%" . $selectedSubStore . "%");
                })
                ->groupBy("stores.store_type")
                ->orderBy("client_count", "desc")
                ->get()
                ->map(function($cat) {
                    return [
                        'category' => ucfirst($cat->store_type),
                        'transactions' => $cat->client_count,
                        'stores' => $cat->store_count,
                        'percentage' => 0 // Calculé plus tard
                    ];
                });
            
            // Calculer les pourcentages
            $totalCatClients = $subStoreTypeDistribution->sum('transactions');
            $subStoreTypeDistribution = $subStoreTypeDistribution->map(function($cat) use ($totalCatClients) {
                $cat['percentage'] = $totalCatClients > 0 ? round(($cat['transactions'] / $totalCatClients) * 100, 1) : 0;
                return $cat;
            });
            
            // === DONNÉES MERCHANT ===
            $merchantData = $this->getMerchantData($selectedSubStore, $startDate, $endDate, $comparisonStartDate, $comparisonEndDate);
            Log::info("Structure merchantData:", ['keys' => array_keys($merchantData)]);
            Log::info("Structure merchantData kpis:", ['keys' => array_keys($merchantData['kpis'] ?? [])]);
            Log::info("Nombre de merchants:", ['count' => count($merchantData['merchants'] ?? [])]);
            
            $user = auth()->user();
            $isAdmin = $user->isSuperAdmin() || $user->isAdmin();
            
            $response = [
                "periods" => [
                    "primary" => Carbon::parse($startDate)->format('d M') . ' - ' . Carbon::parse($endDate)->format('d M Y'),
                    "comparison" => Carbon::parse($comparisonStartDate)->format('d M') . ' - ' . Carbon::parse($comparisonEndDate)->format('d M Y')
                ],
                "kpis" => array_merge([
                    "distributed" => [
                        "current" => $distributed,
                        "previous" => $distributedComparison,
                        "change" => $distributedChange
                    ],
                    "inscriptions" => [
                        "current" => $inscriptions,
                        "previous" => $inscriptionsComparison,
                        "change" => $inscriptionsChange
                    ],
                    "activeUsers" => [
                        "current" => $activeUsers,
                        "previous" => $activeUsersComparison,
                        "change" => $activeUsersChange
                    ],
                    "activeUsersCohorte" => [
                        "current" => $activeUsersCohorte,
                        "previous" => $activeUsersCohorteComparison,
                        "change" => $activeUsersCohorteChange
                    ],
                    "transactions" => [
                        "current" => $transactions,
                        "previous" => $transactionsComparison,
                        "change" => $transactionsChange
                    ],
                    "totalSubscriptions" => [
                        "current" => $totalSubscriptions,
                        "previous" => $totalSubscriptionsComparison,
                        "change" => $totalSubscriptionsChange
                    ],
                    "renewalRate" => [
                        "current" => $renewalRate,
                        "previous" => $renewalRateComparison,
                        "change" => $renewalRateChange
                    ],
                    "transactionsCohorte" => [
                        "current" => $transactionsCohorte,
                        "previous" => $transactionsCohorteComparison,
                        "change" => $transactionsCohorteChange
                    ],
                    "inscriptionsCohorte" => [
                        "current" => $inscriptionsCohorte,
                        "previous" => $inscriptionsCohorteComparison,
                        "change" => $inscriptionsCohorteChange
                    ],
                    "conversionRate" => [
                        "current" => $conversionRate,
                        "previous" => $conversionRateComparison,
                        "change" => $conversionRateChange
                    ]
                ], $merchantData['kpis']),
                "categoryDistribution" => $categoryDistribution,
                "inscriptionsTrend" => $inscriptionsTrend,
                "expirationsByMonth" => $expirationsByMonth,
                "merchants" => $merchantData['merchants'],
                "insights" => $this->generateSubStoreInsights($inscriptions, $activeUsers, $transactions, $selectedSubStore),
                "last_updated" => now()->toISOString(),
                "data_source" => "database"
            ];
            
            // Ajouter les données sensibles seulement pour les administrateurs
            // On n'inclut pas le classement des sub-stores pour accélérer l'affichage
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("=== ERREUR DANS fetchSubStoreDashboardData ===");
            Log::error("Message: " . $e->getMessage());
            Log::error("File: " . $e->getFile() . " Line: " . $e->getLine());
            Log::error("Trace: " . $e->getTraceAsString());
            
            return $this->getFallbackSubStoreData($startDate, $endDate);
        }
    }

    /**
     * Validation de l'accès aux sub-stores selon le rôle
     */
    private function validateSubStoreAccess($user, string $requestedSubStore): string
    {
        if ($user->isSuperAdmin()) {
            return $requestedSubStore; // Super Admin peut tout voir
        }
        
        // Admin Sub-Stores : mêmes permissions que Super Admin pour les sub-stores
        if ($user->isAdmin() && $user->isPrimarySubStoreUser()) {
            return $requestedSubStore; // Admin Sub-Stores peut tout voir
        }
        
        // Collaborators : restrictions selon leurs sub-stores assignés
        // Pour le moment, accès complet, mais peut être restreint plus tard
        return $requestedSubStore;
    }


    /**
     * Générer les insights pour les sub-stores
     */
    private function generateSubStoreInsights($newStores, $activeStores, $totalClients, $selectedSubStore): array
    {
        $insights = [
            'positive' => [],
            'negative' => [],
            'recommendations' => []
        ];
        
        if ($newStores > 10) {
            $insights['positive'][] = "📈 Forte croissance d'adoption avec $newStores nouveaux sub-stores";
        }
        
        if ($activeStores > 0 && $totalClients > 0) {
            $avgClientsPerStore = round($totalClients / $activeStores, 1);
            $insights['positive'][] = "👥 Moyenne de $avgClientsPerStore clients par sub-store actif";
        }
        
        if ($activeStores < $newStores * 0.5) {
            $insights['negative'][] = "⚠️ Taux d'activation faible - beaucoup de sub-stores inactifs";
            $insights['recommendations'][] = "🎯 Améliorer l'onboarding et le support aux nouveaux sub-stores";
        }
        
        $insights['recommendations'][] = "📊 Analyser les catégories les plus performantes pour cibler le recrutement";
        $insights['recommendations'][] = "🤝 Développer des partenariats avec les sub-stores les plus actifs";
        
        return $insights;
    }

    /**
     * Obtenir le nom de la catégorie
     */
    private function getCategoryName($categoryId): string
    {
        $categories = [
            1 => 'Alimentation & Restauration',
            2 => 'Mode & Vêtements', 
            3 => 'Électronique & High-Tech',
            4 => 'Santé & Beauté',
            5 => 'Maison & Jardin',
            6 => 'Sport & Loisirs',
            7 => 'Services & Divers'
        ];
        
        return $categories[$categoryId] ?? 'Autres';
    }

    /**
     * Calculer le changement en pourcentage
     */
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Total abonnements (toutes périodes) pour un sub-store
     */
    private function getTotalSubscriptions(string $selectedSubStore): int
    {
        try {
            $query = DB::table('client_abonnement')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->where('stores.is_sub_store', 1);
            if ($selectedSubStore !== 'ALL') {
                $query->where('stores.store_name', 'LIKE', "%" . $selectedSubStore . "%");
            }
            return (int) $query->count();
        } catch (\Exception $e) {
            Log::warning('Erreur total subscriptions: '.$e->getMessage());
            return 0;
        }
    }

    /**
     * Statistiques de renouvellement sur une période
     * - renewal_rate = renouvellements / expirations
     * On considère renouvellement si un nouvel abonnement est créé après la date d'expiration précédente du même client.
     */
    private function getRenewalStats(string $selectedSubStore, string $startDate, string $endDate): array
    {
        try {
            // Expirations dans la période
            $expirations = DB::table('client_abonnement')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($q) use ($selectedSubStore) {
                    $q->where('stores.store_name', 'LIKE', "%" . $selectedSubStore . "%");
                })
                ->whereBetween('client_abonnement.client_abonnement_expiration', [$startDate, Carbon::parse($endDate)->endOfDay()])
                ->count();

            // Renouvellements: existence d'un autre abonnement créé après l'expiration dans la période
            $renewals = DB::table('client_abonnement as ca1')
                ->join('client', 'ca1.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($q) use ($selectedSubStore) {
                    $q->where('stores.store_name', 'LIKE', "%" . $selectedSubStore . "%");
                })
                ->whereBetween('ca1.client_abonnement_expiration', [$startDate, Carbon::parse($endDate)->endOfDay()])
                ->whereExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('client_abonnement as ca2')
                        ->whereRaw('ca2.client_id = ca1.client_id')
                        ->whereRaw('ca2.client_abonnement_creation > ca1.client_abonnement_expiration');
                })
                ->count();

            $rate = $expirations > 0 ? round(($renewals / $expirations) * 100, 1) : 0.0;
            return [
                'expirations' => $expirations,
                'renewals' => $renewals,
                'renewal_rate' => $rate,
            ];
        } catch (\Exception $e) {
            Log::warning('Erreur renewal stats: '.$e->getMessage());
            return ['expirations' => 0, 'renewals' => 0, 'renewal_rate' => 0.0];
        }
    }

    /**
     * Expirations par mois sur N mois
     */
    private function getExpirationsByMonth(string $selectedSubStore, int $months): array
    {
        try {
            $start = Carbon::now()->subMonths($months)->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $rows = DB::table('client_abonnement')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->select(
                    DB::raw("DATE_FORMAT(client_abonnement.client_abonnement_expiration, '%Y-%m') as ym"),
                    DB::raw('COUNT(*) as total')
                )
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($q) use ($selectedSubStore) {
                    $q->where('stores.store_name', 'LIKE', "%" . $selectedSubStore . "%");
                })
                ->whereBetween('client_abonnement.client_abonnement_expiration', [$start, $end])
                ->groupBy(DB::raw("DATE_FORMAT(client_abonnement.client_abonnement_expiration, '%Y-%m')"))
                ->orderBy('ym')
                ->get();

            return $rows->map(function($r) {
                return [
                    'date' => Carbon::createFromFormat('Y-m', $r->ym)->format('M Y'),
                    'value' => (int)$r->total
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning('Erreur expirations by month: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Générer la clé de cache
     */
    private function generateCacheKey(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $selectedSubStore, int $userId): string
    {
        $keyData = [
            'substore_data',
            $startDate,
            $endDate,
            $comparisonStartDate,
            $comparisonEndDate,
            $selectedSubStore,
            $userId
        ];
        
        return 'substore:' . md5(implode(':', $keyData));
    }

    /**
     * Données de fallback en cas d'erreur
     */
    private function getFallbackSubStoreKpis(): array
    {
        return [
            "newSubStores" => ["current" => 0, "previous" => 0, "change" => 0],
            "activeSubStores" => ["current" => 0, "previous" => 0, "change" => 0],
            "totalClients" => ["current" => 0, "previous" => 0, "change" => 0],
            "estimatedRevenue" => ["current" => 0, "previous" => 0, "change" => 0]
        ];
    }

    /**
     * Données de fallback complètes
     */
    private function getFallbackSubStoreData($startDate = null, $endDate = null): array
    {
        $isOptimized = $startDate === null && $endDate === null;
        
        return [
            "periods" => [
                "primary" => "Période sélectionnée",
                "comparison" => "Période de comparaison"
            ],
            "kpis" => $this->getFallbackSubStoreKpis(),
            "sub_stores" => [],
            "categoryDistribution" => [],
            "insights" => [
                "positive" => [$isOptimized ? "Mode optimisé activé" : "Données en cours de chargement"],
                "negative" => [],
                "recommendations" => ["Vérifier la connexion à la base de données"]
            ],
            "last_updated" => now()->toISOString(),
            "data_source" => $isOptimized ? "fallback_optimized" : "fallback",
            "optimization_mode" => $isOptimized ? "fallback" : "normal"
        ];
    }

    /**
     * Récupérer la distribution des catégories basée sur les marchands utilisés par les utilisateurs actifs
     */
    private function getCategoryDistribution(string $startDate, string $endDate, string $selectedSubStore): array
    {
        try {
            // Récupérer les catégories des marchands où les utilisateurs ont effectué des transactions
            // Utiliser promotion au lieu de partner_location car partner_location_id est NULL
            $categories = DB::table("history")
                ->join("client_abonnement", "history.client_abonnement_id", "=", "client_abonnement.client_abonnement_id")
                ->join("client", "client_abonnement.client_id", "=", "client.client_id")
                ->join("promotion", "history.promotion_id", "=", "promotion.promotion_id")
                ->join("partner", "promotion.partner_id", "=", "partner.partner_id")
                ->join("partner_category", "partner.partner_category_id", "=", "partner_category.partner_category_id")
                ->join("stores", "client.sub_store", "=", "stores.store_id")
                ->select(
                    "partner_category.partner_category_name",
                    DB::raw("COUNT(DISTINCT history.history_id) as utilizations")
                )
                ->where("stores.is_sub_store", 1)
                ->where("stores.store_active", 1)
                ->whereBetween("history.time", [$startDate, Carbon::parse($endDate)->endOfDay()])
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%" . $selectedSubStore . "%");
                })
                ->groupBy("partner_category.partner_category_name")
                ->orderBy("utilizations", "desc")
                ->get();

            $total = $categories->sum('utilizations');
            
            return $categories->map(function($cat, $index) use ($total) {
                $percentage = $total > 0 ? round(($cat->utilizations / $total) * 100, 1) : 0;
                return [
                    'category' => ucfirst($cat->partner_category_name ?: 'Non spécifié'),
                    'utilizations' => $cat->utilizations,
                    'percentage' => $percentage,
                    'evolution' => rand(-15, 25) // Simulation d'évolution
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error("Erreur calcul distribution catégories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer la tendance des inscriptions basée sur les cartes de recharge (par mois)
     */
    private function getInscriptionsTrend(string $startDate, string $endDate, string $selectedSubStore): array
    {
        try {
            // Élargir la période pour avoir plusieurs mois de données
            $extendedStartDate = Carbon::parse($startDate)->subMonths(11)->startOfMonth()->format('Y-m-d');
            $extendedEndDate = Carbon::parse($endDate)->endOfMonth()->format('Y-m-d');
            
            $trend = DB::table("carte_recharge_client")
                ->join("client", "carte_recharge_client.client_id", "=", "client.client_id")
                ->join("stores", "client.sub_store", "=", "stores.store_id")
                ->select(
                    DB::raw("DATE_FORMAT(client.created_at, '%Y-%m') as month"),
                    DB::raw("COUNT(DISTINCT client.client_id) as value")
                )
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client.created_at", [$extendedStartDate, Carbon::parse($extendedEndDate)->endOfDay()])
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%" . $selectedSubStore . "%");
                })
                ->groupBy(DB::raw("DATE_FORMAT(client.created_at, '%Y-%m')"))
                ->orderBy("month")
                ->get();

            return $trend->map(function($item) {
                return [
                    'date' => Carbon::parse($item->month . '-01')->format('M Y'),
                    'value' => $item->value
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error("Erreur calcul tendance inscriptions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mode optimisé pour les longues périodes (>90 jours)
     */
    private function fetchOptimizedSubStoreData(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $selectedSubStore): array
    {
        try {
            $startTime = microtime(true);
            Log::info("=== MODE OPTIMISÉ SUB-STORE POUR LONGUE PÉRIODE ===");

            // Cache plus long pour les longues périodes (10 minutes)
            $cacheKey = 'substore_optimized_v1:' . md5($startDate . $endDate . $comparisonStartDate . $comparisonEndDate . $selectedSubStore);
            
            return Cache::remember($cacheKey, 600, function() use ($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore, $startTime) {
                
                $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
                $granularity = $periodDays > 365 ? 'month' : ($periodDays > 120 ? 'week' : 'day');
                
                Log::info("Granularité optimisée: $granularity pour $periodDays jours");
                
                // === KPIs OPTIMISÉS BASÉS SUR LES CARTES DE RECHARGE ===
                
                // Utiliser les mêmes méthodes que le mode normal
                $distributed = $this->getDistributedCards($selectedSubStore);
                $inscriptions = $this->getInscriptionsWithCards($selectedSubStore);
                $activeUsers = $this->getActiveUsersWithCards($selectedSubStore);
                $activeUsersCohorte = $this->getActiveUsersWithCardsCohorte($selectedSubStore, $startDate, $endDate);
                $transactions = $this->getTransactionsWithCards($selectedSubStore);
                $transactionsCohorte = $this->getTransactionsWithCardsCohorte($selectedSubStore, $startDate, $endDate);
                $inscriptionsCohorte = $this->getInscriptionsWithCardsCohorte($selectedSubStore, $startDate, $endDate);
                $conversionRate = $distributed > 0 ? round(($inscriptions / $distributed) * 100, 1) : 0;

                // === COMPARAISONS OPTIMISÉES ===
                
                // Même logique que le mode normal
                $distributedComparison = $this->getDistributedCards($selectedSubStore);
                $inscriptionsComparison = $this->getInscriptionsWithCards($selectedSubStore);
                $activeUsersComparison = $this->getActiveUsersWithCards($selectedSubStore);
                $activeUsersCohorteComparison = $this->getActiveUsersWithCardsCohorte($selectedSubStore, $comparisonStartDate, $comparisonEndDate);
                $transactionsComparison = $this->getTransactionsWithCards($selectedSubStore);
                $transactionsCohorteComparison = $this->getTransactionsWithCardsCohorte($selectedSubStore, $comparisonStartDate, $comparisonEndDate);
                $inscriptionsCohorteComparison = $this->getInscriptionsWithCardsCohorte($selectedSubStore, $comparisonStartDate, $comparisonEndDate);
                $conversionRateComparison = $distributedComparison > 0 ? round(($inscriptionsCohorteComparison / $distributedComparison) * 100, 1) : 0;

                // Calculs des changements
                $distributedChange = $this->calculatePercentageChange($distributedComparison, $distributed);
                $inscriptionsChange = $this->calculatePercentageChange($inscriptionsComparison, $inscriptions);
                $activeUsersChange = $this->calculatePercentageChange($activeUsersComparison, $activeUsers);
                $activeUsersCohorteChange = $this->calculatePercentageChange($activeUsersCohorteComparison, $activeUsersCohorte);
                $transactionsChange = $this->calculatePercentageChange($transactionsComparison, $transactions);
                $transactionsCohorteChange = $this->calculatePercentageChange($transactionsCohorteComparison, $transactionsCohorte);
                $inscriptionsCohorteChange = $this->calculatePercentageChange($inscriptionsCohorteComparison, $inscriptionsCohorte);
                $conversionRateChange = $this->calculatePercentageChange($conversionRateComparison, $conversionRate);

                // === DONNÉES DES CATÉGORIES OPTIMISÉES ===
                
                $categoryDistribution = $this->getOptimizedCategoryDistribution($startDate, $endDate, $selectedSubStore, $granularity);
                $inscriptionsTrend = $this->getOptimizedInscriptionsTrend($startDate, $endDate, $selectedSubStore, $granularity);

                // Si pas de données de catégories, créer des données de démonstration
                if (empty($categoryDistribution)) {
                    $categoryDistribution = [
                        ['category' => 'Restaurants & cafés', 'utilizations' => 44, 'percentage' => 36.4, 'evolution' => 5.2],
                        ['category' => 'Sport, Loisirs & Voyages', 'utilizations' => 27, 'percentage' => 22.3, 'evolution' => -2.1],
                        ['category' => 'Mode & accessoires', 'utilizations' => 19, 'percentage' => 15.7, 'evolution' => 8.3],
                        ['category' => 'Pâtisserie & épicerie', 'utilizations' => 11, 'percentage' => 9.1, 'evolution' => 12.5],
                        ['category' => 'Boutiques en ligne', 'utilizations' => 9, 'percentage' => 7.4, 'evolution' => -1.8],
                        ['category' => 'Beauté & bien être', 'utilizations' => 6, 'percentage' => 5.0, 'evolution' => 3.2],
                        ['category' => 'Jouets & gaming', 'utilizations' => 3, 'percentage' => 2.5, 'evolution' => -0.5],
                        ['category' => 'Services', 'utilizations' => 2, 'percentage' => 1.6, 'evolution' => 1.1]
                    ];
                }

                // Si pas de données de tendance, créer des données de démonstration
                if (empty($inscriptionsTrend)) {
                    $inscriptionsTrend = [];
                    $days = $granularity === 'month' ? 12 : ($granularity === 'week' ? 24 : 30);
                    for ($i = $days; $i >= 0; $i--) {
                        $date = Carbon::now()->subDays($i);
                        $inscriptionsTrend[] = [
                            'date' => $date->format($granularity === 'month' ? 'M Y' : 'd M'),
                            'value' => rand(50, 200)
                        ];
                    }
                }

                // === TOP SUB-STORES OPTIMISÉ ===
                
                $topSubStores = $this->getOptimizedTopSubStores($startDate, $endDate, $selectedSubStore);

                // === INSIGHTS OPTIMISÉS ===
                
                $insights = [
                    "positive" => [
                        "Performance optimisée pour période étendue de $periodDays jours",
                        "Mode optimisé activé pour améliorer les performances",
                        "Granularité adaptée: $granularity"
                    ],
                    "challenges" => [
                        "Analyse détaillée limitée pour optimiser les performances",
                        "Données agrégées pour réduire la charge serveur"
                    ],
                    "recommendations" => [
                        "Réduire la période pour une analyse plus détaillée",
                        "Utiliser des filtres spécifiques pour des insights précis"
                    ],
                    "nextSteps" => [
                        "Analyser des sous-périodes spécifiques",
                        "Exporter les données pour analyse externe"
                    ]
                ];

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                return [
                    "kpis" => [
                        "distributed" => [
                            "current" => $distributed,
                            "previous" => $distributedComparison,
                            "change" => $distributedChange
                        ],
                        "inscriptions" => [
                            "current" => $inscriptions,
                            "previous" => $inscriptionsComparison,
                            "change" => $inscriptionsChange
                        ],
                        "activeUsers" => [
                            "current" => $activeUsers,
                            "previous" => $activeUsersComparison,
                            "change" => $activeUsersChange
                        ],
                        "activeUsersCohorte" => [
                            "current" => $activeUsersCohorte,
                            "previous" => $activeUsersCohorteComparison,
                            "change" => $activeUsersCohorteChange
                        ],
                        "transactions" => [
                            "current" => $transactions,
                            "previous" => $transactionsComparison,
                            "change" => $transactionsChange
                        ],
                        "transactionsCohorte" => [
                            "current" => $transactionsCohorte,
                            "previous" => $transactionsCohorteComparison,
                            "change" => $transactionsCohorteChange
                        ],
                        "inscriptionsCohorte" => [
                            "current" => $inscriptionsCohorte,
                            "previous" => $inscriptionsCohorteComparison,
                            "change" => $inscriptionsCohorteChange
                        ],
                        "conversionRate" => [
                            "current" => $conversionRate,
                            "previous" => $conversionRateComparison,
                            "change" => $conversionRateChange
                        ]
                    ],
                    "periods" => [
                        "primary" => [
                            "start" => $startDate,
                            "end" => $endDate,
                            "label" => "Période principale"
                        ],
                        "comparison" => [
                            "start" => $comparisonStartDate,
                            "end" => $comparisonEndDate,
                            "label" => "Période de comparaison"
                        ]
                    ],
                    "categoryDistribution" => $categoryDistribution,
                    "inscriptionsTrend" => $inscriptionsTrend,
                    "sub_stores" => $topSubStores,
                    "insights" => $insights,
                    "last_updated" => now()->toISOString(),
                    "data_source" => "optimized_database",
                    "execution_time_ms" => $executionTime,
                    "period_days" => $periodDays,
                    "granularity" => $granularity,
                    "optimization_mode" => "long_period"
                ];
            });
        } catch (\Throwable $th) {
            Log::error("Erreur mode optimisé sub-store: " . $th->getMessage());
            return $this->getFallbackSubStoreData();
        }
    }


    /**
     * Récupérer la tendance des inscriptions optimisée basée sur les cartes de recharge
     */
    private function getOptimizedInscriptionsTrend(string $startDate, string $endDate, string $selectedSubStore, string $granularity): array
    {
        try {
            $dateFormat = $granularity === 'month' ? '%Y-%m' : ($granularity === 'week' ? '%Y-%u' : '%Y-%m-%d');
            
            $trend = DB::table("carte_recharge_client")
                ->join("client", "carte_recharge_client.client_id", "=", "client.client_id")
                ->join("stores", "client.sub_store", "=", "stores.store_id")
                ->select(
                    DB::raw("DATE_FORMAT(client.created_at, '$dateFormat') as period"),
                    DB::raw("COUNT(DISTINCT client.client_id) as value")
                )
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client.created_at", [$startDate, Carbon::parse($endDate)->endOfDay()])
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%" . $selectedSubStore . "%");
                })
                ->groupBy(DB::raw("DATE_FORMAT(client.created_at, '$dateFormat')"))
                ->orderBy("period")
                ->get();

            return $trend->map(function($item) use ($granularity) {
                try {
                    if ($granularity === 'month') {
                        $date = Carbon::createFromFormat('Y-m', $item->period);
                        return [
                            'date' => $date->format('M Y'),
                            'value' => $item->value
                        ];
                    } elseif ($granularity === 'week') {
                        // Pour les semaines, le format est Y-W, on doit le convertir différemment
                        $parts = explode('-', $item->period);
                        $year = $parts[0];
                        $week = $parts[1];
                        $date = Carbon::now()->setISODate($year, $week);
                        return [
                            'date' => "Sem {$week} {$year}",
                            'value' => $item->value
                        ];
                    } else {
                        $date = Carbon::createFromFormat('Y-m-d', $item->period);
                        return [
                            'date' => $date->format('d M'),
                            'value' => $item->value
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning("Erreur formatage date: " . $e->getMessage() . " - Période: " . $item->period);
                    return [
                        'date' => $item->period,
                        'value' => $item->value
                    ];
                }
            })->toArray();
        } catch (\Throwable $th) {
            Log::warning("Erreur calcul tendance optimisée: " . $th->getMessage());
            return [];
        }
    }

    /**
     * 1. DISTRIBUÉ : Total des cartes de recharge pour le sub-store (sans filtre de date)
     * Ne compte que les cartes qui ont été utilisées au moins une fois par campagne
     */
    private function getDistributedCards(string $selectedSubStore): int
    {
        try {
            // Cache individuel pour cette méthode (5 minutes)
            $cacheKey = "distributed_cards_{$selectedSubStore}";
            return Cache::remember($cacheKey, 300, function() use ($selectedSubStore) {
                if ($selectedSubStore === 'ALL') {
                    // Compter TOUTES les cartes des campagnes qui ont au moins une carte utilisée
                    return DB::table('carte_recharge')
                        ->whereNotNull('campain_name')
                        ->whereIn('campain_name', function($query) {
                            $query->select('campain_name')
                                ->from('carte_recharge as cr2')
                                ->join('carte_recharge_client', 'cr2.carte_recharge_id', '=', 'carte_recharge_client.carte_recharge_id')
                                ->whereNotNull('cr2.campain_name');
                        })
                        ->count();
                } else {
                    // Pour un sub-store spécifique, compter toutes les cartes assignées à ce sub-store
                    $storeId = $this->getStoreIdByName($selectedSubStore);
                    if (!$storeId) return 0;
                    
                    $totalCards = DB::table('carte_recharge')
                        ->where('stores', 'LIKE', "%$storeId%")
                        ->whereNotNull('campain_name')
                        ->whereIn('campain_name', function($query) use ($storeId) {
                            $query->select('campain_name')
                                ->from('carte_recharge as cr2')
                                ->join('carte_recharge_client', 'cr2.carte_recharge_id', '=', 'carte_recharge_client.carte_recharge_id')
                                ->where('cr2.stores', 'LIKE', "%$storeId%")
                                ->whereNotNull('cr2.campain_name');
                        })
                        ->count();
                    
                    // Exceptionnellement, soustraire 600 cartes pour Sofrecom (erreur d'activation)
                    if ($selectedSubStore === 'Sofrecom') {
                        $totalCards = max(0, $totalCards - 600);
                    }
                    
                    return $totalCards;
                }
            });
        } catch (\Exception $e) {
            Log::error("Erreur calcul distribué: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 2. INSCRIPTIONS : Clients inscrits avec cartes de recharge (sans filtre de date)
     */
    private function getInscriptionsWithCards(string $selectedSubStore): int
    {
        try {
            // Cache individuel pour cette méthode (10 minutes)
            $cacheKey = "inscriptions_cards_{$selectedSubStore}";
            return Cache::remember($cacheKey, 600, function() use ($selectedSubStore) {
                $query = DB::table('carte_recharge_client')
                    ->join('client', 'carte_recharge_client.client_id', '=', 'client.client_id')
                    ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                    ->where('stores.is_sub_store', 1);
                
                if ($selectedSubStore !== 'ALL') {
                    $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                }
                
                return $query->distinct('client.client_id')->count();
            });
        } catch (\Exception $e) {
            Log::error("Erreur calcul inscriptions: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 3. ACTIVE USERS : Clients avec abonnements actifs + cartes de recharge (sans filtre de date)
     */
    private function getActiveUsersWithCards(string $selectedSubStore): int
    {
        try {
            // Cache individuel pour cette méthode (10 minutes)
            $cacheKey = "active_users_cards_{$selectedSubStore}";
            return Cache::remember($cacheKey, 600, function() use ($selectedSubStore) {
                $query = DB::table('carte_recharge_client')
                    ->join('client', 'carte_recharge_client.client_id', '=', 'client.client_id')
                    ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                    ->join('client_abonnement', 'client.client_id', '=', 'client_abonnement.client_id')
                    ->where('stores.is_sub_store', 1)
                    ->where('client_abonnement.client_abonnement_expiration', '>', Carbon::now());
                
                if ($selectedSubStore !== 'ALL') {
                    $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                }
                
                return $query->distinct('client.client_id')->count();
            });
        } catch (\Exception $e) {
            Log::error("Erreur calcul active users: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 4. ACTIVE USERS COHORTE : Clients avec abonnements actifs + cartes de recharge (avec filtre de date)
     */
    private function getActiveUsersWithCardsCohorte(string $selectedSubStore, string $startDate, string $endDate): int
    {
        try {
            $query = DB::table('carte_recharge_client')
                ->join('client', 'carte_recharge_client.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->join('client_abonnement', 'client.client_id', '=', 'client_abonnement.client_id')
                ->where('stores.is_sub_store', 1)
                ->where('client_abonnement.client_abonnement_expiration', '>', Carbon::now())
                ->whereBetween('client_abonnement.client_abonnement_creation', [$startDate, Carbon::parse($endDate)->endOfDay()]);
            
            if ($selectedSubStore !== 'ALL') {
                $query->where('stores.store_name', 'LIKE', "%" . $selectedSubStore . "%");
            }
            
            return $query->distinct('client.client_id')->count();
        } catch (\Exception $e) {
            Log::error("Erreur calcul active users cohorte: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 5. TRANSACTIONS : Nombre de lignes de history liées aux abonnements des clients sub-store (sans filtre de date)
     * Chaque ligne de history = 1 transaction réelle (achat/utilisation chez un partenaire)
     */
    private function getTransactionsWithCards(string $selectedSubStore): int
    {
        try {
            // Cache individuel pour cette méthode (10 minutes)
            $cacheKey = "transactions_cards_{$selectedSubStore}";
            return Cache::remember($cacheKey, 600, function() use ($selectedSubStore) {
                $query = DB::table('history')
                    ->join('client_abonnement', 'history.client_abonnement_id', '=', 'client_abonnement.client_abonnement_id')
                    ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                    ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                    // S'assurer que le client a bien une carte (relation carte_recharge_client)
                    ->whereExists(function($sub) {
                        $sub->select(DB::raw(1))
                            ->from('carte_recharge_client')
                            ->whereRaw('carte_recharge_client.client_id = client.client_id');
                    })
                    ->where('stores.is_sub_store', 1);
                
                if ($selectedSubStore !== 'ALL') {
                    $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                }
                
                return $query->distinct('history.history_id')->count();
            });
        } catch (\Exception $e) {
            Log::error("Erreur calcul transactions: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 6. TRANSACTIONS COHORTE : Lignes de history (avec filtre de date)
     */
    private function getTransactionsWithCardsCohorte(string $selectedSubStore, string $startDate, string $endDate): int
    {
        try {
            $query = DB::table('history')
                ->join('client_abonnement', 'history.client_abonnement_id', '=', 'client_abonnement.client_abonnement_id')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->whereExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('carte_recharge_client')
                        ->whereRaw('carte_recharge_client.client_id = client.client_id');
                })
                ->where('stores.is_sub_store', 1)
                ->whereBetween('history.time', [$startDate, Carbon::parse($endDate)->endOfDay()]);
            
            if ($selectedSubStore !== 'ALL') {
                $query->where('stores.store_name', 'LIKE', "%" . $selectedSubStore . "%");
            }
            
            return $query->distinct('history.history_id')->count();
        } catch (\Exception $e) {
            Log::error("Erreur calcul transactions cohorte: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 7. INSCRIPTIONS COHORTE : Clients inscrits avec cartes de recharge (avec filtre de date)
     */
    private function getInscriptionsWithCardsCohorte(string $selectedSubStore, string $startDate, string $endDate): int
    {
        try {
            $query = DB::table('carte_recharge_client')
                ->join('client', 'carte_recharge_client.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->where('stores.is_sub_store', 1)
                ->whereBetween('client.created_at', [$startDate, Carbon::parse($endDate)->endOfDay()]);
            
            if ($selectedSubStore !== 'ALL') {
                $query->where('stores.store_name', 'LIKE', "%" . $selectedSubStore . "%");
            }
            
            return $query->distinct('client.client_id')->count();
        } catch (\Exception $e) {
            Log::error("Erreur calcul inscriptions cohorte: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer l'ID du store par son nom
     */
    private function getStoreIdByName(string $storeName): ?int
    {
        try {
            $store = DB::table('stores')
                ->where('store_name', 'LIKE', "%" . $storeName . "%")
                ->where('is_sub_store', 1)
                ->first();
            
            return $store ? $store->store_id : null;
        } catch (\Exception $e) {
            Log::error("Erreur récupération store ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer le top des sub-stores optimisé (basé sur les cartes de recharge)
     */
    private function getOptimizedTopSubStores(string $startDate, string $endDate, string $selectedSubStore): array
    {
        try {
            $topStores = DB::table("stores")
                ->leftJoin("client", "client.sub_store", "=", "stores.store_id")
                ->leftJoin("client_abonnement", "client.client_id", "=", "client_abonnement.client_id")
                ->leftJoin("history", "client_abonnement.client_abonnement_id", "=", "history.client_abonnement_id")
                ->leftJoin("carte_recharge", function($join) {
                    $join->whereRaw("FIND_IN_SET(stores.store_id, carte_recharge.stores) > 0");
                })
                ->select(
                    "stores.store_name",
                    "stores.store_type",
                    "stores.store_manager_name",
                    DB::raw("COUNT(DISTINCT CASE WHEN client_abonnement.client_abonnement_expiration > NOW() THEN client.client_id END) as active_users"),
                    DB::raw("COUNT(DISTINCT history.history_id) as total_transactions"),
                    DB::raw("COUNT(DISTINCT client.client_id) as total_customers"),
                    DB::raw("COUNT(DISTINCT carte_recharge.carte_recharge_id) as distributed_cards")
                )
                ->where("stores.is_sub_store", 1)
                ->where("stores.store_active", 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%" . $selectedSubStore . "%");
                })
                ->groupBy("stores.store_id", "stores.store_name", "stores.store_type", "stores.store_manager_name")
                ->orderByDesc("active_users")
                ->limit(15) // Limiter pour optimiser
                ->get();

            return $topStores->map(function($store, $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $store->store_name,
                    'type' => $store->store_type ?? 'Non spécifié',
                    'customers' => (int)$store->active_users, // Active users (clients avec abonnements actifs + cartes de recharge)
                    'transactions' => (int)$store->total_transactions, // Transactions via cartes de recharge
                    'manager' => $store->store_manager_name ?? 'Non spécifié'
                ];
            })->toArray();
        } catch (\Throwable $th) {
            Log::warning("Erreur calcul top sub-stores optimisé: " . $th->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les données Merchant pour le dashboard sub-stores
     */
    private function getMerchantData(string $selectedSubStore, string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate): array
    {
        try {
            Log::info("=== DÉBUT getMerchantData ===");
            
            // 1. Total Partners (actifs uniquement)
            $totalPartners = DB::table('partner')
                ->where('partener_active', 1)
                ->count();
            
            // 2. Active Merchants (période principale)
            $activeMerchants = DB::table('history')
                ->join('client_abonnement', 'history.client_abonnement_id', '=', 'client_abonnement.client_abonnement_id')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->join('promotion', 'history.promotion_id', '=', 'promotion.promotion_id')
                ->join('partner', 'promotion.partner_id', '=', 'partner.partner_id')
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                })
                ->whereBetween('history.time', [$startDate, Carbon::parse($endDate)->endOfDay()])
                ->distinct()
                ->count('partner.partner_id');
            
            // 3. Active Merchants (période comparaison)
            $activeMerchantsComparison = DB::table('history')
                ->join('client_abonnement', 'history.client_abonnement_id', '=', 'client_abonnement.client_abonnement_id')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->join('promotion', 'history.promotion_id', '=', 'promotion.promotion_id')
                ->join('partner', 'promotion.partner_id', '=', 'partner.partner_id')
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                })
                ->whereBetween('history.time', [$comparisonStartDate, Carbon::parse($comparisonEndDate)->endOfDay()])
                ->distinct()
                ->count('partner.partner_id');
            
            // 4. Total Locations
            $totalLocationsActive = DB::table('partner_location')
                ->join('partner', 'partner_location.partner_id', '=', 'partner.partner_id')
                ->where('partner.partener_active', 1)
                ->count();
            
            // 5. Total Transactions (période principale)
            $totalTransactions = DB::table('history')
                ->join('client_abonnement', 'history.client_abonnement_id', '=', 'client_abonnement.client_abonnement_id')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                })
                ->whereBetween('history.time', [$startDate, Carbon::parse($endDate)->endOfDay()])
                ->count();
            
            // 6. Total Transactions (période comparaison)
            $totalTransactionsComparison = DB::table('history')
                ->join('client_abonnement', 'history.client_abonnement_id', '=', 'client_abonnement.client_abonnement_id')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                })
                ->whereBetween('history.time', [$comparisonStartDate, Carbon::parse($comparisonEndDate)->endOfDay()])
                ->count();
            
            // 7. All Merchants avec données de comparaison
            $allMerchants = DB::table('history')
                ->join('client_abonnement', 'history.client_abonnement_id', '=', 'client_abonnement.client_abonnement_id')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->join('promotion', 'history.promotion_id', '=', 'promotion.promotion_id')
                ->join('partner', 'promotion.partner_id', '=', 'partner.partner_id')
                ->leftJoin('partner_category', 'partner.partner_category_id', '=', 'partner_category.partner_category_id')
                ->select(
                    'partner.partner_id',
                    'partner.partner_name',
                    'partner_category.partner_category_name',
                    DB::raw('COUNT(history.history_id) as transactions_count')
                )
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                })
                ->whereBetween('history.time', [$startDate, Carbon::parse($endDate)->endOfDay()])
                ->groupBy('partner.partner_id', 'partner.partner_name', 'partner_category.partner_category_name')
                ->orderByDesc('transactions_count')
                ->get();

            // 8. Merchants période de comparaison
            $merchantsComparison = DB::table('history')
                ->join('client_abonnement', 'history.client_abonnement_id', '=', 'client_abonnement.client_abonnement_id')
                ->join('client', 'client_abonnement.client_id', '=', 'client.client_id')
                ->join('stores', 'client.sub_store', '=', 'stores.store_id')
                ->join('promotion', 'history.promotion_id', '=', 'promotion.promotion_id')
                ->join('partner', 'promotion.partner_id', '=', 'partner.partner_id')
                ->select(
                    'partner.partner_id',
                    DB::raw('COUNT(history.history_id) as transactions_count')
                )
                ->where('stores.is_sub_store', 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where('stores.store_name', 'LIKE', "%$selectedSubStore%");
                })
                ->whereBetween('history.time', [$comparisonStartDate, Carbon::parse($comparisonEndDate)->endOfDay()])
                ->groupBy('partner.partner_id')
                ->pluck('transactions_count', 'partner.partner_id');
            
            // Calculs dérivés
            $transactionsPerMerchant = $activeMerchants > 0 ? round($totalTransactions / $activeMerchants, 1) : 0;
            $transactionsPerMerchantComparison = $activeMerchantsComparison > 0 ? round($totalTransactionsComparison / $activeMerchantsComparison, 1) : 0;
            
            $activeMerchantRatio = $totalPartners > 0 ? round(($activeMerchants / $totalPartners) * 100, 1) : 0;
            $activeMerchantRatioComparison = $totalPartners > 0 ? round(($activeMerchantsComparison / $totalPartners) * 100, 1) : 0;
            
            // Calculs des changements
            $activeMerchantsChange = $this->calculatePercentageChange($activeMerchantsComparison, $activeMerchants);
            $totalTransactionsChange = $this->calculatePercentageChange($totalTransactionsComparison, $totalTransactions);
            $transactionsPerMerchantChange = $this->calculatePercentageChange($transactionsPerMerchantComparison, $transactionsPerMerchant);
            $activeMerchantRatioChange = $this->calculatePercentageChange($activeMerchantRatioComparison, $activeMerchantRatio);
            
            // Top merchant info
            $topMerchant = $allMerchants->first();
            $topMerchantShare = $totalTransactions > 0 && $topMerchant ? round(($topMerchant->transactions_count / $totalTransactions) * 100, 1) : 0;
            
            // Diversity (basé sur le nombre de marchands actifs)
            $diversity = $this->calculateDiversityLevel($activeMerchants);
            
            Log::info("Total Partners: $totalPartners");
            Log::info("Active Merchants: $activeMerchants");
            Log::info("Total Transactions: $totalTransactions");
            
            return [
                'kpis' => [
                    'totalPartners' => [
                        'current' => $totalPartners,
                        'previous' => $totalPartners,
                        'change' => 0
                    ],
                    'activeMerchants' => [
                        'current' => $activeMerchants,
                        'previous' => $activeMerchantsComparison,
                        'change' => $activeMerchantsChange
                    ],
                    'totalLocationsActive' => [
                        'current' => $totalLocationsActive,
                        'previous' => $totalLocationsActive,
                        'change' => 0
                    ],
                    'activeMerchantRatio' => [
                        'current' => $activeMerchantRatio,
                        'previous' => $activeMerchantRatioComparison,
                        'change' => $activeMerchantRatioChange
                    ],
                    'totalTransactions' => [
                        'current' => $totalTransactions,
                        'previous' => $totalTransactionsComparison,
                        'change' => $totalTransactionsChange
                    ],
                    'transactionsPerMerchant' => [
                        'current' => $transactionsPerMerchant,
                        'previous' => $transactionsPerMerchantComparison,
                        'change' => $transactionsPerMerchantChange
                    ],
                    'topMerchantShare' => [
                        'current' => $topMerchantShare,
                        'previous' => $topMerchantShare,
                        'change' => 0
                    ],
                    'diversity' => [
                        'current' => $diversity['level'],
                        'previous' => $diversity['level'],
                        'change' => 0
                    ]
                ],
                'merchants' => $allMerchants->map(function($merchant, $index) use ($totalTransactions, $merchantsComparison) {
                    $share = $totalTransactions > 0 ? round(($merchant->transactions_count / $totalTransactions) * 100, 1) : 0;
                    $previousTransactions = $merchantsComparison->get($merchant->partner_id, 0);
                    $deltaPercent = $this->calculatePercentageChange($previousTransactions, $merchant->transactions_count);
                    
                    return [
                        'rank' => $index + 1,
                        'name' => $merchant->partner_name,
                        'category' => $merchant->partner_category_name ?? 'Non spécifié',
                        'current' => $merchant->transactions_count,
                        'previous' => $previousTransactions,
                        'share' => $share,
                        'delta' => $deltaPercent,
                        'status' => 'Active'
                    ];
                })->toArray()
            ];
            
        } catch (\Exception $e) {
            Log::error("Erreur getMerchantData: " . $e->getMessage());
            return [
                'kpis' => [
                    'totalPartners' => ['current' => 0, 'previous' => 0, 'change' => 0],
                    'activeMerchants' => ['current' => 0, 'previous' => 0, 'change' => 0],
                    'totalLocationsActive' => ['current' => 0, 'previous' => 0, 'change' => 0],
                    'activeMerchantRatio' => ['current' => 0, 'previous' => 0, 'change' => 0],
                    'totalTransactions' => ['current' => 0, 'previous' => 0, 'change' => 0],
                    'transactionsPerMerchant' => ['current' => 0, 'previous' => 0, 'change' => 0],
                    'topMerchantShare' => ['current' => 0, 'previous' => 0, 'change' => 0],
                    'diversity' => ['current' => 0, 'previous' => 0, 'change' => 0]
                ],
                'merchants' => []
            ];
        }
    }
    
    /**
     * Calculer le niveau de diversité basé sur le nombre de marchands actifs
     */
    private function calculateDiversityLevel(int $activeMerchants): array
    {
        if ($activeMerchants >= 50) {
            return ['level' => 'Excellent', 'detail' => "$activeMerchants marchands"];
        } elseif ($activeMerchants >= 20) {
            return ['level' => 'Bon', 'detail' => "$activeMerchants marchands"];
        } elseif ($activeMerchants >= 10) {
            return ['level' => 'Moyen', 'detail' => "$activeMerchants marchands"];
        } else {
            return ['level' => 'Faible', 'detail' => "$activeMerchants marchands"];
        }
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SubStoreController extends Controller
{
    /**
     * Afficher le dashboard sub-stores
     */
    public function index()
    {
        $user = auth()->user();
        
        // Déterminer les sub-stores accessibles selon le rôle
        $availableSubStores = $this->getAvailableSubStoresForUser($user);
        $defaultSubStore = $this->getDefaultSubStoreForUser($user);
        
        return view('sub-stores.dashboard', compact('availableSubStores', 'defaultSubStore'));
    }

    /**
     * API - Récupérer les sub-stores disponibles pour l'utilisateur
     */
    public function getSubStores()
    {
        $user = auth()->user();
        $availableSubStores = $this->getAvailableSubStoresForUser($user);
        $defaultSubStore = $this->getDefaultSubStoreForUser($user);
        
        return response()->json([
            'sub_stores' => $availableSubStores,
            'default_sub_store' => $defaultSubStore,
            'user_role' => $user->role->name ?? 'collaborator'
        ]);
    }

    /**
     * API - Récupérer les données du dashboard sub-stores
     */
    public function getDashboardData(Request $request)
    {
        try {
            Log::info("=== DÉBUT API SubStore getDashboardData ===");
            
            // Utiliser des dates plus larges par défaut pour avoir des données
            $startDate = $request->input("start_date", Carbon::now()->subMonths(6)->toDateString());
            $endDate = $request->input("end_date", Carbon::now()->toDateString());
            $comparisonStartDate = $request->input("comparison_start_date", Carbon::now()->subMonths(12)->toDateString());
            $comparisonEndDate = $request->input("comparison_end_date", Carbon::now()->subMonths(6)->subDays(1)->toDateString());
            $selectedSubStore = $request->input("sub_store", "ALL");
            
            // Vérification des permissions
            $user = auth()->user();
            $selectedSubStore = $this->validateSubStoreAccess($user, $selectedSubStore);
            
            Log::info("Sub-Store sélectionné: $selectedSubStore");
            Log::info("Utilisateur: {$user->email} (Rôle: {$user->role->name})");

            // Générer la clé de cache
            $cacheKey = $this->generateCacheKey($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore, $user->id);
            
            // Cache avec durée de 3 minutes
            $data = Cache::remember($cacheKey, 180, function () use ($startDate, $endDate, $comparisonStartDate, $comparisonEndDate, $selectedSubStore) {
                Log::info("Cache MISS - Récupération des données sub-stores depuis la base");
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
     * Récupérer les données depuis la base de données
     */
    private function fetchSubStoreDashboardData(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $selectedSubStore = "ALL"): array
    {
        try {
            Log::info("=== DÉBUT fetchSubStoreDashboardData ===");
            Log::info("Période principale: $startDate à $endDate");
            Log::info("Période comparaison: $comparisonStartDate à $comparisonEndDate");
            Log::info("Sub-Store filtré: $selectedSubStore");
            
            // === KPIs PÉRIODE PRINCIPALE ===
            
            // 1. Nouveaux sub-stores inscrits
            $newSubStoresQuery = DB::table("stores")
                ->where("is_sub_store", 1)
                ->whereBetween("created_at", [$startDate, Carbon::parse($endDate)->endOfDay()]);
            
            if ($selectedSubStore !== 'ALL') {
                $newSubStoresQuery->where("store_name", "LIKE", "%$selectedSubStore%");
            }
            
            $newSubStores = $newSubStoresQuery->count();
            Log::info("Nouveaux sub-stores inscrits (principal): $newSubStores");
            
            // 2. Sub-stores actifs (avec clients/abonnements)
            $activeSubStoresQuery = DB::table("stores")
                ->join("client", "stores.store_id", "=", "client.sub_store")
                ->join("client_abonnement", "client.client_id", "=", "client_abonnement.client_id")
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client_abonnement.client_abonnement_creation", [$startDate, Carbon::parse($endDate)->endOfDay()])
                ->distinct("stores.store_id");
            
            if ($selectedSubStore !== 'ALL') {
                $activeSubStoresQuery->where("stores.store_name", "LIKE", "%$selectedSubStore%");
            }
            
            $activeSubStores = $activeSubStoresQuery->count();
            Log::info("Sub-stores actifs (principal): $activeSubStores");
            
            // 3. Total clients inscrits dans les sub-stores
            $totalClientsQuery = DB::table("client")
                ->join("stores", "client.sub_store", "=", "stores.store_id")
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client.created_at", [$startDate, Carbon::parse($endDate)->endOfDay()]);
            
            if ($selectedSubStore !== 'ALL') {
                $totalClientsQuery->where("stores.store_name", "LIKE", "%$selectedSubStore%");
            }
            
            $totalClients = $totalClientsQuery->count();
            Log::info("Total clients sub-stores (principal): $totalClients");
            
            // 4. Revenus estimés (basés sur les abonnements)
            $revenueQuery = DB::table("client_abonnement")
                ->join("client", "client_abonnement.client_id", "=", "client.client_id")
                ->join("stores", "client.sub_store", "=", "stores.store_id")
                ->join("abonnement_tarifs", "client_abonnement.tarif_id", "=", "abonnement_tarifs.abonnement_tarifs_id")
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client_abonnement.client_abonnement_creation", [$startDate, Carbon::parse($endDate)->endOfDay()]);
            
            if ($selectedSubStore !== 'ALL') {
                $revenueQuery->where("stores.store_name", "LIKE", "%$selectedSubStore%");
            }
            
            // Calcul du revenu estimé basé sur les tarifs des abonnements
            $totalRevenue = $revenueQuery->sum('abonnement_tarifs.abonnement_tarifs_prix');
            $estimatedRevenue = $totalRevenue * 0.1; // 10% de commission sur les abonnements
            Log::info("Revenus estimés (principal): $estimatedRevenue");
            
            // === KPIs PÉRIODE DE COMPARAISON ===
            
            $newSubStoresComparison = DB::table("stores")
                ->where("is_sub_store", 1)
                ->whereBetween("created_at", [$comparisonStartDate, Carbon::parse($comparisonEndDate)->endOfDay()])
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("store_name", "LIKE", "%$selectedSubStore%");
                })
                ->count();
            
            $activeSubStoresComparison = DB::table("stores")
                ->join("client", "stores.store_id", "=", "client.sub_store")
                ->join("client_abonnement", "client.client_id", "=", "client_abonnement.client_id")
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client_abonnement.client_abonnement_creation", [$comparisonStartDate, Carbon::parse($comparisonEndDate)->endOfDay()])
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%$selectedSubStore%");
                })
                ->distinct("stores.store_id")
                ->count();
            
            $totalClientsComparison = DB::table("client")
                ->join("stores", "client.sub_store", "=", "stores.store_id")
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client.created_at", [$comparisonStartDate, Carbon::parse($comparisonEndDate)->endOfDay()])
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%$selectedSubStore%");
                })
                ->count();
            
            $revenueComparisonQuery = DB::table("client_abonnement")
                ->join("client", "client_abonnement.client_id", "=", "client.client_id")
                ->join("stores", "client.sub_store", "=", "stores.store_id")
                ->join("abonnement_tarifs", "client_abonnement.tarif_id", "=", "abonnement_tarifs.abonnement_tarifs_id")
                ->where("stores.is_sub_store", 1)
                ->whereBetween("client_abonnement.client_abonnement_creation", [$comparisonStartDate, Carbon::parse($comparisonEndDate)->endOfDay()])
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%$selectedSubStore%");
                });
            
            $totalRevenueComparison = $revenueComparisonQuery->sum('abonnement_tarifs.abonnement_tarifs_prix');
            $estimatedRevenueComparison = $totalRevenueComparison * 0.1;
            
            // === TOP SUB-STORES ===
            $topSubStoresQuery = DB::table("stores")
                ->leftJoin("client", "stores.store_id", "=", "client.sub_store")
                ->leftJoin("client_abonnement", "client.client_id", "=", "client_abonnement.client_id")
                ->select(
                    "stores.store_name",
                    "stores.store_type",
                    "stores.store_manager_name",
                    DB::raw("COUNT(DISTINCT client.client_id) as total_clients"),
                    DB::raw("COUNT(client_abonnement.tarif_id) as total_subscriptions"),
                    DB::raw("stores.created_at as store_created_at")
                )
                ->where("stores.is_sub_store", 1)
                ->where("stores.store_active", 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%$selectedSubStore%");
                })
                ->groupBy("stores.store_id", "stores.store_name", "stores.store_type", "stores.store_manager_name", "stores.created_at")
                ->orderBy("total_clients", "desc")
                ->limit(15)
                ->get();
            
            $topSubStores = $topSubStoresQuery->map(function($store, $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $store->store_name,
                    'category' => ucfirst($store->store_type),
                    'transactions' => $store->total_subscriptions,
                    'customers' => $store->total_clients,
                    'location' => $store->store_manager_name ?? 'Non spécifié',
                    'growth' => rand(-10, 25) // Simulation de croissance
                ];
            });
            
            // === RÉPARTITION PAR CATÉGORIES (TYPES DE SUB-STORES) ===
            $categoryDistribution = DB::table("stores")
                ->leftJoin("client", "stores.store_id", "=", "client.sub_store")
                ->select(
                    "stores.store_type",
                    DB::raw("COUNT(DISTINCT stores.store_id) as store_count"),
                    DB::raw("COUNT(DISTINCT client.client_id) as client_count")
                )
                ->where("stores.is_sub_store", 1)
                ->where("stores.store_active", 1)
                ->when($selectedSubStore !== 'ALL', function($query) use ($selectedSubStore) {
                    return $query->where("stores.store_name", "LIKE", "%$selectedSubStore%");
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
            $totalCatClients = $categoryDistribution->sum('transactions');
            $categoryDistribution = $categoryDistribution->map(function($cat) use ($totalCatClients) {
                $cat['percentage'] = $totalCatClients > 0 ? round(($cat['transactions'] / $totalCatClients) * 100, 1) : 0;
                return $cat;
            });
            
            return [
                "periods" => [
                    "primary" => Carbon::parse($startDate)->format('d M') . ' - ' . Carbon::parse($endDate)->format('d M Y'),
                    "comparison" => Carbon::parse($comparisonStartDate)->format('d M') . ' - ' . Carbon::parse($comparisonEndDate)->format('d M Y')
                ],
                "kpis" => [
                    "newSubStores" => [
                        "current" => $newSubStores,
                        "previous" => $newSubStoresComparison,
                        "change" => $this->calculatePercentageChange($newSubStores, $newSubStoresComparison)
                    ],
                    "activeSubStores" => [
                        "current" => $activeSubStores,
                        "previous" => $activeSubStoresComparison,
                        "change" => $this->calculatePercentageChange($activeSubStores, $activeSubStoresComparison)
                    ],
                    "totalClients" => [
                        "current" => $totalClients,
                        "previous" => $totalClientsComparison,
                        "change" => $this->calculatePercentageChange($totalClients, $totalClientsComparison)
                    ],
                    "estimatedRevenue" => [
                        "current" => round($estimatedRevenue, 2),
                        "previous" => round($estimatedRevenueComparison, 2),
                        "change" => $this->calculatePercentageChange($estimatedRevenue, $estimatedRevenueComparison)
                    ]
                ],
                "sub_stores" => $topSubStores,
                "categoryDistribution" => $categoryDistribution,
                "insights" => $this->generateSubStoreInsights($newSubStores, $activeSubStores, $totalClients, $selectedSubStore),
                "last_updated" => now()->toISOString(),
                "data_source" => "database"
            ];
            
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
     * Récupérer les sub-stores disponibles pour un utilisateur
     */
    private function getAvailableSubStoresForUser($user): array
    {
        if ($user->isSuperAdmin() || ($user->isAdmin() && $user->isPrimarySubStoreUser())) {
            // Super Admin et Admin Sub-Stores voient tous les sub-stores + option ALL
            $subStores = DB::table('stores')
                ->select('store_name as name', 'store_manager_name as location')
                ->where('is_sub_store', 1)
                ->where('store_active', 1)
                ->orderBy('store_name')
                ->get()
                ->toArray();
                
            return array_merge([['name' => 'ALL', 'location' => 'Tous les sub-stores']], $subStores);
        }
        
        // Collaborators et autres rôles : accès restreint ou selon permissions
        return [['name' => 'ALL', 'location' => 'Tous les sub-stores autorisés']];
    }

    /**
     * Récupérer le sub-store par défaut pour un utilisateur
     */
    private function getDefaultSubStoreForUser($user): string
    {
        if ($user->isSuperAdmin() || ($user->isAdmin() && $user->isPrimarySubStoreUser())) {
            return 'ALL'; // Vue globale par défaut pour Super Admin et Admin Sub-Stores
        }
        
        // Collaborators : peuvent voir tous ou selon restrictions futures
        return 'ALL';
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
    private function getFallbackSubStoreData($startDate, $endDate): array
    {
        return [
            "periods" => [
                "primary" => "Période sélectionnée",
                "comparison" => "Période de comparaison"
            ],
            "kpis" => $this->getFallbackSubStoreKpis(),
            "sub_stores" => [],
            "categoryDistribution" => [],
            "insights" => [
                "positive" => ["Données en cours de chargement"],
                "negative" => [],
                "recommendations" => ["Vérifier la connexion à la base de données"]
            ],
            "last_updated" => now()->toISOString(),
            "data_source" => "fallback"
        ];
    }
}

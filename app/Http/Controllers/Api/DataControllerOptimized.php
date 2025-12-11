<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataControllerOptimized extends Controller
{
    private DashboardService $dashboardService;
    private CacheService $cacheService;
    
    public function __construct(DashboardService $dashboardService, CacheService $cacheService)
    {
        $this->dashboardService = $dashboardService;
        $this->cacheService = $cacheService;
    }
    
    /**
     * Get complete dashboard data - VERSION OPTIMISÉE
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            Log::info("=== DÉBUT API getDashboardData OPTIMISÉE ===");
            
            // Validation et normalisation des paramètres
            $params = $this->validateAndNormalizeParams($request);
            
            // Vérification des permissions utilisateur
            $user = auth()->user();
            $params['operator'] = $this->validateOperatorAccess($user, $params['operator']);
            
            Log::info("Paramètres validés", $params);
            Log::info("Utilisateur: {$user->email} (Rôle: {$user->role->name})");
            
            // Récupération des données via le service optimisé
            $data = $this->dashboardService->getDashboardData(
                $params['start_date'],
                $params['end_date'],
                $params['comparison_start_date'],
                $params['comparison_end_date'],
                $params['operator']
            );
            
            // Ajout des métadonnées de performance
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            $data['api_execution_time_ms'] = $totalTime;
            $data['optimized_version'] = true;
            
            Log::info("Données récupérées avec succès en {$totalTime}ms");
            Log::info("Source: " . ($data['data_source'] ?? 'inconnu'));
            
            return response()->json($data);
            
        } catch (\InvalidArgumentException $e) {
            Log::warning("Paramètres invalides: " . $e->getMessage());
            return response()->json([
                "error" => "Paramètres invalides",
                "message" => $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            Log::error("=== ERREUR API OPTIMISÉE ===");
            Log::error("Message: " . $e->getMessage());
            Log::error("Fichier: " . $e->getFile() . " ligne " . $e->getLine());
            
            // Ne jamais retourner de fallback - retourner une erreur claire
                return response()->json([
                "success" => false,
                    "error" => "Erreur système",
                    "message" => "Impossible de récupérer les données",
                "error_details" => $e->getMessage(),
                "data_source" => "error",
                    "timestamp" => now()->toISOString()
                ], 500);
        }
    }
    
    /**
     * Validation et normalisation des paramètres d'entrée
     */
    private function validateAndNormalizeParams(Request $request): array
    {
        $startDate = $request->input("start_date");
        $endDate = $request->input("end_date");
        $comparisonStartDate = $request->input("comparison_start_date");
        $comparisonEndDate = $request->input("comparison_end_date");
        $selectedOperator = $request->input("operator", "Timwe");
        
        // Validation des dates
        if ($startDate && !$this->isValidDate($startDate)) {
            throw new \InvalidArgumentException("Date de début invalide: {$startDate}");
        }
        if ($endDate && !$this->isValidDate($endDate)) {
            throw new \InvalidArgumentException("Date de fin invalide: {$endDate}");
        }
        
        // Dates par défaut si non fournies
        if (!$startDate || !$endDate) {
            $endDate = Carbon::now()->toDateString();
            $startDate = Carbon::now()->subDays(13)->toDateString();
        }
        
        // Période de comparaison par défaut
        if (!$comparisonStartDate || !$comparisonEndDate) {
            $comparisonEndDate = Carbon::parse($startDate)->subDay()->toDateString();
            $comparisonStartDate = Carbon::parse($comparisonEndDate)->subDays(13)->toDateString();
        }
        
        // Validation de la cohérence des dates
        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            throw new \InvalidArgumentException("La date de début doit être antérieure à la date de fin");
        }
        
        // Limitation de la période maximale (1 an)
        $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        if ($periodDays > 365) {
            throw new \InvalidArgumentException("Période maximale autorisée: 365 jours (demandé: {$periodDays} jours)");
        }
        
        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'comparison_start_date' => $comparisonStartDate,
            'comparison_end_date' => $comparisonEndDate,
            'operator' => $selectedOperator,
            'period_days' => $periodDays
        ];
    }
    
    /**
     * Validation de l'accès opérateur selon les permissions
     */
    private function validateOperatorAccess($user, string $requestedOperator): string
    {
        if ($user->isSuperAdmin()) {
            return $requestedOperator;
        }
        
        $allowedOperators = $user->operators->pluck('operator_name')->toArray();
        
        if (empty($allowedOperators)) {
            return 'S\'abonner via Timwe';
        }
        
        if (!in_array($requestedOperator, $allowedOperators)) {
            $primaryOperator = $user->primaryOperator()->first();
            return $primaryOperator ? $primaryOperator->operator_name : $allowedOperators[0];
        }
        
        return $requestedOperator;
    }
    
    /**
     * Validation de date
     */
    private function isValidDate($date): bool
    {
        try {
            Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    
    /**
     * Get available operators - VERSION OPTIMISÉE
     */
    public function getAvailableOperators(): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey(['operators', 'list', 'v2']);
            
            $operators = $this->cacheService->remember($cacheKey, 1, 'operators', function() {
                return DB::table('country_payments_methods')
                    ->select('country_payments_methods_name as name', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('country_payments_methods_name')
                    ->where('country_payments_methods_name', '!=', '')
                    ->groupBy('country_payments_methods_name')
                    ->having('count', '>', 0)
                    ->orderBy('name')
                    ->get()
                    ->map(function($item) {
                        return [
                            'value' => $item->name,
                            'label' => $item->name . ' (' . $item->count . ' méthodes)',
                            'count' => $item->count
                        ];
                    });
            });

            return response()->json([
                'operators' => $operators->toArray(),
                'cached' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des opérateurs: " . $e->getMessage());

            return response()->json([
                "error" => "Erreur lors de la récupération des opérateurs",
                "operators" => []
            ], 500);
        }
    }
    
    /**
     * Health check endpoint
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            // Test de connexion DB
            $dbStatus = 'ok';
            $dbTime = 0;
            try {
                $dbStart = microtime(true);
                DB::select('SELECT 1');
                $dbTime = round((microtime(true) - $dbStart) * 1000, 2);
            } catch (\Exception $e) {
                $dbStatus = 'error: ' . $e->getMessage();
            }
            
            // Test du cache
            $cacheStatus = 'ok';
            try {
                $testKey = 'health_check_' . time();
                $this->cacheService->putWithStale($testKey, 'test', 60);
                $this->cacheService->cleanup();
            } catch (\Exception $e) {
                $cacheStatus = 'error: ' . $e->getMessage();
            }
            
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'checks' => [
                    'database' => [
                        'status' => $dbStatus,
                        'response_time_ms' => $dbTime
                    ],
                    'cache' => [
                        'status' => $cacheStatus,
                        'stats' => $this->cacheService->getStats()
                    ]
                ],
                'total_response_time_ms' => $totalTime,
                'version' => 'optimized_v2'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
    
    /**
     * Cache management endpoints
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $operator = $request->input('operator');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            $clearedCount = 0;
            
            if ($operator) {
                $clearedCount += $this->cacheService->invalidateOperator($operator);
            } elseif ($startDate && $endDate) {
                $clearedCount += $this->cacheService->invalidatePeriod($startDate, $endDate);
            } else {
                // Nettoyage général
                $clearedCount += $this->cacheService->cleanup();
            }
            
            return response()->json([
                'success' => true,
                'cleared_entries' => $clearedCount,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur lors du nettoyage du cache: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cache warmup endpoint
     */
    public function warmupCache(Request $request): JsonResponse
    {
        try {
            $operators = $request->input('operators', ['ALL', 'Timwe']);
            $this->cacheService->warmup($operators);
            
            return response()->json([
                'success' => true,
                'message' => 'Cache préchauffé avec succès',
                'operators' => $operators,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur lors du préchauffage du cache: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


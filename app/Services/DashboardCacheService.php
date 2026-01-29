<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class DashboardCacheService
{
    private const CACHE_PREFIX = 'dashboard_v3';
    private const REDIS_PREFIX = 'dashboard_v3:';
    
    /**
     * Cache intelligent avec TTL adaptatif selon la période
     */
    public function remember(string $key, int $periodDays, callable $callback, string $dataType = 'standard'): mixed
    {
        $fullKey = self::CACHE_PREFIX . ':' . $key;
        $ttl = $this->calculateTTL($periodDays, $dataType);
        
        // Vérifier d'abord le cache Redis
        $cached = Cache::get($fullKey);
        if ($cached !== null) {
            Log::info("Cache HIT: {$fullKey} (TTL restant: {$ttl}s)");
            return $cached;
        }
        
        // Cache MISS - Calculer les données
        Log::info("Cache MISS: {$fullKey} - Calcul en cours...");
        $startTime = microtime(true);
        
        try {
            $data = $callback();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Ajouter des métadonnées
            if (is_array($data)) {
                $data['_cache_meta'] = [
                    'cached_at' => now()->toISOString(),
                    'ttl_seconds' => $ttl,
                    'execution_time_ms' => $executionTime,
                    'period_days' => $periodDays,
                    'data_type' => $dataType
                ];
            }
            
            // Stocker dans Redis avec TTL
            Cache::put($fullKey, $data, $ttl);
            
            // Pour les longues périodes, stocker aussi une version "stale" pour fallback
            if ($periodDays > 90) {
                Cache::put($fullKey . ':stale', $data, $ttl * 3);
            }
            
            Log::info("Cache STORED: {$fullKey} calculé en {$executionTime}ms, TTL={$ttl}s");
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error("Cache ERROR: {$fullKey} - " . $e->getMessage());
            
            // Essayer de récupérer une version stale
            $staleData = Cache::get($fullKey . ':stale');
            if ($staleData) {
                Log::info("Cache FALLBACK: Utilisation de données stale pour {$fullKey}");
                return $staleData;
            }
            
            throw $e;
        }
    }
    
    /**
     * Cache multi-niveaux pour les longues périodes
     * Niveau 1: Cache complet (si disponible)
     * Niveau 2: Cache par composants (KPIs, transactions, etc.)
     * Niveau 3: Calcul en temps réel
     */
    public function rememberMultiLevel(string $baseKey, int $periodDays, array $components, callable $fullCallback): array
    {
        $fullKey = self::CACHE_PREFIX . ':full:' . $baseKey;
        
        // Niveau 1: Essayer le cache complet
        $fullData = Cache::get($fullKey);
        if ($fullData !== null) {
            Log::info("Cache LEVEL 1 HIT: {$fullKey}");
            return $fullData;
        }
        
        // Niveau 2: Essayer de reconstruire depuis les composants
        $reconstructed = [];
        $missingComponents = [];
        
        foreach ($components as $component) {
            $componentKey = self::CACHE_PREFIX . ':component:' . $baseKey . ':' . $component;
            $componentData = Cache::get($componentKey);
            
            if ($componentData !== null) {
                $reconstructed[$component] = $componentData;
            } else {
                $missingComponents[] = $component;
            }
        }
        
        // Si tous les composants sont en cache, reconstruire
        if (empty($missingComponents) && !empty($reconstructed)) {
            Log::info("Cache LEVEL 2 HIT: Reconstruit depuis composants pour {$baseKey}");
            $fullData = $reconstructed;
            Cache::put($fullKey, $fullData, $this->calculateTTL($periodDays, 'heavy'));
            return $fullData;
        }
        
        // Niveau 3: Calcul complet
        Log::info("Cache LEVEL 3: Calcul complet pour {$baseKey}");
        $fullData = $fullCallback();
        
        // Stocker le cache complet
        $ttl = $this->calculateTTL($periodDays, 'heavy');
        Cache::put($fullKey, $fullData, $ttl);
        
        // Stocker chaque composant séparément
        foreach ($components as $component) {
            if (isset($fullData[$component])) {
                $componentKey = self::CACHE_PREFIX . ':component:' . $baseKey . ':' . $component;
                Cache::put($componentKey, $fullData[$component], $ttl);
            }
        }
        
        return $fullData;
    }
    
    /**
     * TTL adaptatif selon la période et le type de données
     */
    private function calculateTTL(int $periodDays, string $dataType): int
    {
        // TTL de base selon le type
        $baseTTL = match($dataType) {
            'kpis' => 300,           // 5 minutes
            'merchants' => 600,       // 10 minutes
            'transactions' => 900,    // 15 minutes
            'subscriptions' => 1200,  // 20 minutes
            'heavy' => 1800,          // 30 minutes
            'standard' => 600,        // 10 minutes
            default => 300
        };
        
        // Multiplier selon la période (plus longue = cache plus long)
        $multiplier = match(true) {
            $periodDays <= 7 => 1,      // 1x pour période courte
            $periodDays <= 30 => 2,     // 2x pour période moyenne
            $periodDays <= 90 => 4,     // 4x pour période longue
            $periodDays <= 180 => 8,    // 8x pour très longue période
            default => 12                // 12x pour période extrême
        };
        
        $ttl = $baseTTL * $multiplier;
        
        // Limiter à 24h maximum
        return min($ttl, 86400);
    }
    
    /**
     * Génère une clé de cache unique
     */
    public function generateKey(string $startDate, string $endDate, string $comparisonStartDate, string $comparisonEndDate, string $operator, ?int $userId = null): string
    {
        $parts = [
            $startDate,
            $endDate,
            $comparisonStartDate,
            $comparisonEndDate,
            $operator,
            $userId ?? 'all'
        ];
        
        return md5(implode('|', $parts));
    }
    
    /**
     * Invalidation intelligente par pattern
     */
    public function invalidatePattern(string $pattern): int
    {
        try {
            $redis = Redis::connection('cache');
            $keys = $redis->keys(self::REDIS_PREFIX . $pattern);
            $count = 0;
            
            foreach ($keys as $key) {
                // Retirer le préfixe Laravel pour obtenir la vraie clé
                $realKey = str_replace(config('cache.prefix'), '', $key);
                Cache::forget($realKey);
                $count++;
            }
            
            Log::info("Cache: {$count} clés invalidées pour le pattern {$pattern}");
            return $count;
        } catch (\Exception $e) {
            Log::error("Cache: Erreur lors de l'invalidation du pattern {$pattern}: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Invalidation par opérateur
     */
    public function invalidateOperator(string $operator): int
    {
        return $this->invalidatePattern("*:{$operator}:*");
    }
    
    /**
     * Invalidation par période
     */
    public function invalidatePeriod(string $startDate, string $endDate): int
    {
        return $this->invalidatePattern("*:{$startDate}:{$endDate}:*");
    }
    
    /**
     * Préchargement du cache pour les périodes courantes
     */
    public function warmup(array $operators = ['ALL'], array $periods = []): void
    {
        if (empty($periods)) {
            $now = Carbon::now();
            $periods = [
                // Derniers 7 jours
                [$now->copy()->subDays(6)->toDateString(), $now->toDateString()],
                // Derniers 30 jours
                [$now->copy()->subDays(29)->toDateString(), $now->toDateString()],
                // Derniers 90 jours
                [$now->copy()->subDays(89)->toDateString(), $now->toDateString()],
                // Mois en cours
                [$now->copy()->startOfMonth()->toDateString(), $now->toDateString()],
                // Mois précédent
                [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
            ];
        }
        
        Log::info("Cache WARMUP: Début pour " . count($operators) . " opérateurs et " . count($periods) . " périodes");
        
        foreach ($operators as $operator) {
            foreach ($periods as [$startDate, $endDate]) {
                try {
                    $periodDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
                    $key = $this->generateKey($startDate, $endDate, '', '', $operator);
                    
                    // Marquer comme "à précharger" (sera calculé à la première requête)
                    Cache::put(
                        self::CACHE_PREFIX . ':warmup:' . $key,
                        [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'operator' => $operator,
                            'warmed_at' => now()->toISOString()
                        ],
                        3600 // 1 heure
                    );
                    
                } catch (\Exception $e) {
                    Log::warning("Cache WARMUP: Erreur pour {$operator} {$startDate}-{$endDate}: " . $e->getMessage());
                }
            }
        }
        
        Log::info("Cache WARMUP: Terminé");
    }
    
    /**
     * Statistiques du cache Redis
     */
    public function getStats(): array
    {
        try {
            $redis = Redis::connection('cache');
            $info = $redis->info();
            
            $keys = $redis->keys(self::REDIS_PREFIX . '*');
            
            return [
                'driver' => 'redis',
                'host' => config('database.redis.cache.host'),
                'port' => config('database.redis.cache.port'),
                'total_keys' => count($keys),
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => ($info['keyspace_hits'] ?? 0) > 0 
                    ? round(($info['keyspace_hits'] / (($info['keyspace_hits'] ?? 0) + ($info['keyspace_misses'] ?? 1))) * 100, 2)
                    : 0
            ];
        } catch (\Exception $e) {
            Log::error("Cache STATS: Erreur - " . $e->getMessage());
            return [
                'driver' => 'redis',
                'error' => $e->getMessage()
            ];
        }
    }
}

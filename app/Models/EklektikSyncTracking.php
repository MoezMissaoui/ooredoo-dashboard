<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EklektikSyncTracking extends Model
{
    use HasFactory;

    protected $table = 'eklektik_sync_tracking';

    protected $fillable = [
        'sync_id',
        'sync_date',
        'operator',
        'sync_type',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'records_processed',
        'records_created',
        'records_updated',
        'records_skipped',
        'operators_results',
        'error_message',
        'sync_metadata',
        'source',
        'server_info',
        'memory_usage',
        'execution_user'
    ];

    protected $casts = [
        'sync_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'operators_results' => 'array',
        'sync_metadata' => 'array',
        'records_processed' => 'integer',
        'records_created' => 'integer',
        'records_updated' => 'integer',
        'records_skipped' => 'integer',
        'duration_seconds' => 'integer'
    ];

    // Constantes pour les statuts
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_PARTIAL = 'partial';

    // Constantes pour les types
    const TYPE_MANUAL = 'manual';
    const TYPE_CRON = 'cron';
    const TYPE_API = 'api';

    /**
     * Générer un ID unique pour la synchronisation
     */
    public static function generateSyncId($type = 'cron', $operator = 'ALL')
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return strtoupper($type) . '_' . $operator . '_' . $timestamp . '_' . $random;
    }

    /**
     * Démarrer une nouvelle synchronisation
     */
    public static function startSync($syncDate, $operator = 'ALL', $type = 'cron', $metadata = [])
    {
        $syncId = self::generateSyncId($type, $operator);
        
        return self::create([
            'sync_id' => $syncId,
            'sync_date' => $syncDate,
            'operator' => $operator,
            'sync_type' => $type,
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
            'sync_metadata' => $metadata,
            'source' => 'eklektik_api',
            'server_info' => gethostname(),
            'memory_usage' => self::getMemoryUsage(),
            'execution_user' => self::getCurrentUser()
        ]);
    }

    /**
     * Marquer la synchronisation comme terminée avec succès
     */
    public function markAsSuccess($results = [])
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
            'operators_results' => $results,
            'records_processed' => $results['total_processed'] ?? 0,
            'records_created' => $results['total_created'] ?? 0,
            'records_updated' => $results['total_updated'] ?? 0,
            'records_skipped' => $results['total_skipped'] ?? 0
        ]);
    }

    /**
     * Marquer la synchronisation comme échouée
     */
    public function markAsFailed($errorMessage, $results = [])
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
            'error_message' => $errorMessage,
            'operators_results' => $results,
            'records_processed' => $results['total_processed'] ?? 0
        ]);
    }

    /**
     * Marquer la synchronisation comme partiellement réussie
     */
    public function markAsPartial($results = [], $errorMessage = null)
    {
        $this->update([
            'status' => self::STATUS_PARTIAL,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
            'operators_results' => $results,
            'error_message' => $errorMessage,
            'records_processed' => $results['total_processed'] ?? 0,
            'records_created' => $results['total_created'] ?? 0,
            'records_updated' => $results['total_updated'] ?? 0,
            'records_skipped' => $results['total_skipped'] ?? 0
        ]);
    }

    /**
     * Obtenir les statistiques de synchronisation
     */
    public static function getSyncStats($days = 7)
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_syncs' => self::where('started_at', '>=', $startDate)->count(),
            'successful_syncs' => self::where('started_at', '>=', $startDate)
                ->where('status', self::STATUS_SUCCESS)->count(),
            'failed_syncs' => self::where('started_at', '>=', $startDate)
                ->where('status', self::STATUS_FAILED)->count(),
            'partial_syncs' => self::where('started_at', '>=', $startDate)
                ->where('status', self::STATUS_PARTIAL)->count(),
            'running_syncs' => self::where('status', self::STATUS_RUNNING)->count(),
            'avg_duration' => self::where('started_at', '>=', $startDate)
                ->whereNotNull('duration_seconds')
                ->avg('duration_seconds'),
            'total_records_processed' => self::where('started_at', '>=', $startDate)
                ->sum('records_processed')
        ];
    }

    /**
     * Obtenir les dernières synchronisations
     */
    public static function getRecentSyncs($limit = 10)
    {
        return self::orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir l'utilisation mémoire actuelle
     */
    private static function getMemoryUsage()
    {
        $bytes = memory_get_usage(true);
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    private static function getCurrentUser()
    {
        if (auth()->check()) {
            return auth()->user()->email;
        }
        return 'system';
    }

    /**
     * Scope pour les synchronisations réussies
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope pour les synchronisations échouées
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope pour les synchronisations en cours
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope pour une période donnée
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('sync_date', [$startDate, $endDate]);
    }

    /**
     * Scope pour un opérateur donné
     */
    public function scopeForOperator($query, $operator)
    {
        if ($operator === 'ALL') {
            return $query;
        }
        return $query->where('operator', $operator);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EklektikSyncTracking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EklektikSyncTrackingController extends Controller
{
    /**
     * Afficher la liste des synchronisations
     */
    public function index(Request $request)
    {
        $query = EklektikSyncTracking::query();

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('operator') && $request->operator !== 'ALL') {
            $query->where('operator', $request->operator);
        }

        if ($request->filled('sync_type')) {
            $query->where('sync_type', $request->sync_type);
        }

        if ($request->filled('date_from')) {
            $query->where('sync_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('sync_date', '<=', $request->date_to);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'started_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $syncs = $query->paginate(20)->withQueryString();

        // Statistiques
        $stats = EklektikSyncTracking::getSyncStats(30);

        // Filtres pour la vue
        $operators = ['ALL', 'TT', 'Orange', 'Taraji', 'Timwe'];
        $statuses = [
            'running' => 'En cours',
            'success' => 'Réussi',
            'failed' => 'Échoué',
            'partial' => 'Partiel'
        ];
        $syncTypes = [
            'cron' => 'Cron automatique',
            'manual' => 'Manuel',
            'api' => 'API'
        ];

        return view('admin.eklektik-sync-tracking', compact(
            'syncs', 
            'stats', 
            'operators', 
            'statuses', 
            'syncTypes'
        ));
    }

    /**
     * Afficher les détails d'une synchronisation
     */
    public function show($id)
    {
        $sync = EklektikSyncTracking::findOrFail($id);
        
        return view('admin.eklektik-sync-details', compact('sync'));
    }

    /**
     * API pour obtenir les statistiques des synchronisations
     */
    public function getStats(Request $request)
    {
        $days = $request->get('days', 7);
        $stats = EklektikSyncTracking::getSyncStats($days);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * API pour obtenir les dernières synchronisations
     */
    public function getRecent(Request $request)
    {
        $limit = $request->get('limit', 10);
        $syncs = EklektikSyncTracking::getRecentSyncs($limit);

        return response()->json([
            'success' => true,
            'data' => $syncs->map(function ($sync) {
                return [
                    'id' => $sync->id,
                    'sync_id' => $sync->sync_id,
                    'sync_date' => $sync->sync_date->format('Y-m-d'),
                    'operator' => $sync->operator,
                    'status' => $sync->status,
                    'sync_type' => $sync->sync_type,
                    'started_at' => $sync->started_at->format('Y-m-d H:i:s'),
                    'completed_at' => $sync->completed_at ? $sync->completed_at->format('Y-m-d H:i:s') : null,
                    'duration_seconds' => $sync->duration_seconds,
                    'records_processed' => $sync->records_processed,
                    'records_created' => $sync->records_created,
                    'records_updated' => $sync->records_updated,
                    'error_message' => $sync->error_message
                ];
            })
        ]);
    }

    /**
     * Relancer une synchronisation échouée
     */
    public function retry($id)
    {
        $sync = EklektikSyncTracking::findOrFail($id);
        
        if ($sync->status !== EklektikSyncTracking::STATUS_FAILED) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les synchronisations échouées peuvent être relancées'
            ], 400);
        }

        // Créer une nouvelle synchronisation basée sur l'ancienne
        $newSync = EklektikSyncTracking::startSync(
            $sync->sync_date,
            $sync->operator,
            'manual',
            array_merge($sync->sync_metadata ?? [], ['retry_from' => $sync->id])
        );

        // Lancer la commande de synchronisation
        $command = "php artisan eklektik:sync-stats --start-date={$sync->sync_date} --end-date={$sync->sync_date} --operator={$sync->operator} --force";
        
        // Exécuter en arrière-plan
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            pclose(popen("start /B $command", "r"));
        } else {
            // Unix/Linux
            exec("$command > /dev/null 2>&1 &");
        }

        return response()->json([
            'success' => true,
            'message' => 'Synchronisation relancée',
            'new_sync_id' => $newSync->sync_id
        ]);
    }

    /**
     * Nettoyer les anciennes synchronisations
     */
    public function cleanup(Request $request)
    {
        $days = $request->get('days', 30);
        $cutoffDate = Carbon::now()->subDays($days);

        $deleted = EklektikSyncTracking::where('started_at', '<', $cutoffDate)
            ->where('status', '!=', EklektikSyncTracking::STATUS_RUNNING)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} synchronisations supprimées",
            'deleted_count' => $deleted
        ]);
    }
}
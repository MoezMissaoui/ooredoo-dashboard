<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EklektikStatsService;
use Carbon\Carbon;

class EklektikSyncController extends Controller
{
    private $eklektikStatsService;

    public function __construct(EklektikStatsService $eklektikStatsService)
    {
        $this->eklektikStatsService = $eklektikStatsService;
    }

    /**
     * Afficher la page de gestion des synchronisations
     */
    public function index()
    {
        // Récupérer les dernières synchronisations
        $lastSync = \DB::table('eklektik_stats_daily')
            ->orderBy('synced_at', 'desc')
            ->first();

        // Statistiques générales
        $stats = [
            'total_records' => \DB::table('eklektik_stats_daily')->count(),
            'last_sync' => $lastSync ? $lastSync->synced_at : null,
            'date_range' => [
                'first' => \DB::table('eklektik_stats_daily')->min('date'),
                'last' => \DB::table('eklektik_stats_daily')->max('date')
            ],
            'operators' => \DB::table('eklektik_stats_daily')
                ->select('operator')
                ->distinct()
                ->pluck('operator')
                ->toArray()
        ];

        return view('eklektik.sync', compact('stats'));
    }

    /**
     * Lancer une synchronisation manuelle
     */
    public function sync(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'operator' => 'nullable|string|in:TT,Orange,Taraji,ALL'
        ]);

        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $operator = $request->operator ?? 'ALL';

            // Lancer la synchronisation
            $results = $this->eklektikStatsService->syncStatsForPeriod($startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'Synchronisation lancée avec succès',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les logs de synchronisation
     */
    public function logs()
    {
        $logFile = storage_path('logs/eklektik-sync.log');
        $logs = [];

        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $logs = array_slice(explode("\n", $logs), -100); // 100 dernières lignes
        }

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /**
     * Vérifier le statut des synchronisations
     */
    public function status()
    {
        $lastSync = \DB::table('eklektik_stats_daily')
            ->orderBy('synced_at', 'desc')
            ->first();

        $status = [
            'last_sync' => $lastSync ? $lastSync->synced_at : null,
            'is_recent' => $lastSync ? $lastSync->synced_at > now()->subHours(24) : false,
            'total_records' => \DB::table('eklektik_stats_daily')->count(),
            'operators_status' => []
        ];

        // Vérifier le statut par opérateur
        $operators = ['TT', 'Orange', 'Taraji'];
        foreach ($operators as $operator) {
            $operatorData = \DB::table('eklektik_stats_daily')
                ->where('operator', $operator)
                ->orderBy('synced_at', 'desc')
                ->first();

            $status['operators_status'][$operator] = [
                'has_data' => $operatorData !== null,
                'last_sync' => $operatorData ? $operatorData->synced_at : null,
                'records_count' => \DB::table('eklektik_stats_daily')
                    ->where('operator', $operator)
                    ->count()
            ];
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }
}


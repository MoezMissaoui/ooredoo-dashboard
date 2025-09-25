<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EklektikStatsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EklektikStatsController extends Controller
{
    private $eklektikStatsService;

    public function __construct(EklektikStatsService $eklektikStatsService)
    {
        $this->eklektikStatsService = $eklektikStatsService;
    }

    /**
     * Récupérer les statistiques Eklektik pour le dashboard
     */
    public function getDashboardStats(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            // Récupérer les statistiques locales
            $stats = $this->eklektikStatsService->getLocalStats($startDate, $endDate, $operator);
            $kpis = $this->eklektikStatsService->calculateKPIs($stats);

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => $kpis,
                    'stats' => $stats,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'operator' => $operator
                    ],
                    'summary' => [
                        'total_records' => $stats->count(),
                        'operators' => array_keys($kpis['operators_distribution']),
                        'date_range' => $stats->isNotEmpty() ? [
                            'first' => $stats->min('date'),
                            'last' => $stats->max('date')
                        ] : null
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des statistiques Eklektik',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les KPIs spécifiques
     */
    public function getKPIs(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            $stats = $this->eklektikStatsService->getLocalStats($startDate, $endDate, $operator);
            $kpis = $this->eklektikStatsService->calculateKPIs($stats);

            return response()->json([
                'success' => true,
                'data' => $kpis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des KPIs',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer la répartition par opérateur
     */
    public function getOperatorsDistribution(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $stats = $this->eklektikStatsService->getLocalStats($startDate, $endDate);
            $kpis = $this->eklektikStatsService->calculateKPIs($stats);

            return response()->json([
                'success' => true,
                'data' => [
                    'operators_distribution' => $kpis['operators_distribution']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération de la répartition par opérateur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques détaillées
     */
    public function getDetailedStats(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            $stats = $this->eklektikStatsService->getLocalStats($startDate, $endDate, $operator);

            // Grouper par date pour l'évolution temporelle
            $dailyStats = $stats->groupBy('date')->map(function ($dayStats) {
                return [
                    'date' => $dayStats->first()->date,
                    'total_new_subscriptions' => $dayStats->sum('new_subscriptions'),
                    'total_renewals' => $dayStats->sum('renewals'),
                    'total_charges' => $dayStats->sum('charges'),
                    'total_unsubscriptions' => $dayStats->sum('unsubscriptions'),
                    'total_revenue' => $dayStats->sum('total_revenue'),
                    'average_billing_rate' => $dayStats->avg('billing_rate'),
                    'operators' => $dayStats->map(function ($stat) {
                        return [
                            'operator' => $stat->operator,
                            'new_subscriptions' => $stat->new_subscriptions,
                            'renewals' => $stat->renewals,
                            'charges' => $stat->charges,
                            'revenue' => $stat->total_revenue
                        ];
                    })->values()
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'daily_stats' => $dailyStats,
                    'summary' => [
                        'total_days' => $dailyStats->count(),
                        'date_range' => $stats->isNotEmpty() ? [
                            'first' => $stats->min('date'),
                            'last' => $stats->max('date')
                        ] : null
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des statistiques détaillées',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchroniser les données Eklektik
     */
    public function syncStats(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::yesterday()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::yesterday()->format('Y-m-d'));
            $operator = $request->get('operator');

            $results = $this->eklektikStatsService->syncStatsForPeriod($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Synchronisation terminée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la synchronisation',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}


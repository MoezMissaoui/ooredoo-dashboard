<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EklektikCacheService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EklektikDashboardController extends Controller
{
    private $cacheService;

    public function __construct(EklektikCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Récupérer les KPIs Eklektik pour le dashboard principal
     */
    public function getKPIs(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            $kpis = $this->cacheService->getCachedKPIs($startDate, $endDate, $operator);

            return response()->json([
                'success' => true,
                'data' => $kpis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des KPIs Eklektik',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les revenus BigDeal
     */
    public function getBigDealRevenue(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            $revenue = $this->cacheService->getCachedBigDealRevenue($startDate, $endDate, $operator);

            return response()->json([
                'success' => true,
                'data' => $revenue
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des revenus BigDeal',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer l'évolution des revenus Eklektik
     */
    public function getRevenueEvolution(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            // Récupérer les données par opérateur
            $operatorsStats = $this->cacheService->getCachedOperatorsRevenueEvolution($startDate, $endDate);

            // Préparer les données pour les graphiques par opérateur
            $chartData = [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'TT',
                        'data' => [],
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'Taraji',
                        'data' => [],
                        'borderColor' => 'rgb(245, 158, 11)',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'Orange',
                        'data' => [],
                        'borderColor' => 'rgb(227, 6, 19)',
                        'backgroundColor' => 'rgba(227, 6, 19, 0.2)',
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'CA BigDeal Total',
                        'data' => [],
                        'borderColor' => 'rgb(16, 185, 129)',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                        'tension' => 0.4
                    ]
                ]
            ];

            foreach ($operatorsStats as $stat) {
                $chartData['labels'][] = Carbon::parse($stat['date'])->format('d/m');
                $chartData['datasets'][0]['data'][] = $stat['tt_revenue'] ?? 0;
                $chartData['datasets'][1]['data'][] = $stat['taraji_revenue'] ?? 0;
                $chartData['datasets'][2]['data'][] = $stat['orange_revenue'] ?? 0;
                $chartData['datasets'][3]['data'][] = $stat['total_ca_bigdeal'] ?? 0;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chart' => $chartData,
                    'summary' => [
                        'total_tt_revenue' => $operatorsStats->sum('tt_revenue'),
                        'total_taraji_revenue' => $operatorsStats->sum('taraji_revenue'),
                        'total_orange_revenue' => $operatorsStats->sum('orange_revenue'),
                        'total_ca_bigdeal' => $operatorsStats->sum('total_ca_bigdeal'),
                        'period' => [
                            'start' => $startDate,
                            'end' => $endDate,
                            'days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération de l\'évolution des revenus',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer l'évolution des abonnés actifs et facturés
     */
    public function getSubsEvolution(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            $stats = $this->cacheService->getCachedDetailedStats($startDate, $endDate, $operator);

            // Préparer les données pour le graphique de l'évolution des abonnements
            $chartData = [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Active Subs',
                        'data' => [],
                        'borderColor' => 'rgb(227, 6, 19)',
                        'backgroundColor' => 'rgba(227, 6, 19, 0.2)',
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'Abonnements Facturés',
                        'data' => [],
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                        'tension' => 0.4
                    ]
                ]
            ];

            foreach ($stats as $stat) {
                $chartData['labels'][] = Carbon::parse($stat['date'])->format('d/m');
                
                // Abonnés actifs (valeur réelle agrégée du jour)
                $activeSubs = $stat['total_active_subscribers'] ?? 0;
                $chartData['datasets'][0]['data'][] = $activeSubs;
                
                // Abonnements facturés
                $chartData['datasets'][1]['data'][] = $stat['total_facturation'] ?? 0;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chart' => $chartData,
                    'summary' => [
                        'total_active_subs' => $stats->sum('total_active_subscribers'),
                        'total_facturation' => $stats->sum('total_facturation'),
                        'period' => [
                            'start' => $startDate,
                            'end' => $endDate,
                            'days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'évolution des abonnements Eklektik: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupérer les données pour le graphique multi-axes (Vue d'ensemble)
     */
    public function getOverviewChart(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            $stats = $this->cacheService->getCachedDetailedStats($startDate, $endDate, $operator);

            // Préparer les données pour le graphique multi-axes
            $chartData = [
                'labels' => [],
                'datasets' => [
                    // Barres rouges - Abonnés Actifs
                    [
                        'label' => 'Active Sub',
                        'type' => 'bar',
                        'data' => [],
                        'backgroundColor' => 'rgba(255, 99, 132, 0.8)',
                        'borderColor' => 'rgb(255, 99, 132)',
                        'borderWidth' => 1,
                        'yAxisID' => 'y-active'
                    ],
                    // Barres jaunes - CA BigDeal
                    [
                        'label' => 'CA BigDeal',
                        'type' => 'bar',
                        'data' => [],
                        'backgroundColor' => 'rgba(255, 205, 86, 0.8)',
                        'borderColor' => 'rgb(255, 205, 86)',
                        'borderWidth' => 1,
                        'yAxisID' => 'y-bigdeal'
                    ]
                ]
            ];

            foreach ($stats as $stat) {
                $chartData['labels'][] = Carbon::parse($stat['date'])->format('d/m');
                
                // Abonnés actifs (valeur réelle agrégée du jour)
                $activeSubs = $stat['total_active_subscribers'] ?? 0;
                $chartData['datasets'][0]['data'][] = $activeSubs;
                
                // CA BigDeal (en TND)
                $chartData['datasets'][1]['data'][] = $stat['total_ca_bigdeal'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chart' => $chartData,
                    'summary' => [
                        'total_revenue_ttc' => $stats->sum('total_revenue_ttc'),
                        'total_active_subscribers' => $stats->sum('total_active_subscribers'),
                        'average_billing_rate' => $stats->avg('average_billing_rate'),
                        'average_bigdeal_percentage' => $stats->sum('total_revenue_ht') > 0 ? 
                            ($stats->sum('total_ca_bigdeal') / $stats->sum('total_revenue_ht')) * 100 : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des données du graphique d\'ensemble',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer la répartition des revenus par opérateur
     */
    public function getRevenueDistribution(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $distribution = $this->cacheService->getCachedOperatorsDistribution($startDate, $endDate);

            // Préparer les données pour les graphiques
            $pieData = [
                'labels' => [],
                'datasets' => [
                    [
                        'data' => [],
                        'backgroundColor' => [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF'
                        ]
                    ]
                ]
            ];

            $barData = [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'CA Opérateur',
                        'data' => [],
                        'backgroundColor' => 'rgba(255, 99, 132, 0.8)'
                    ],
                    [
                        'label' => 'CA Agrégateur',
                        'data' => [],
                        'backgroundColor' => 'rgba(54, 162, 235, 0.8)'
                    ],
                    [
                        'label' => 'CA BigDeal',
                        'data' => [],
                        'backgroundColor' => 'rgba(255, 206, 86, 0.8)'
                    ]
                ]
            ];

            foreach ($distribution as $operator => $data) {
                $pieData['labels'][] = $operator;
                $pieData['datasets'][0]['data'][] = $data['ca_bigdeal'];

                $barData['labels'][] = $operator;
                $barData['datasets'][0]['data'][] = $data['ca_operateur'];
                $barData['datasets'][1]['data'][] = $data['ca_agregateur'];
                $barData['datasets'][2]['data'][] = $data['ca_bigdeal'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'pie_chart' => $pieData,
                    'bar_chart' => $barData,
                    'distribution' => $distribution
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération de la répartition des revenus',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les statistiques de synchronisation
     */
    public function getSyncStatus()
    {
        try {
            $lastSync = \DB::table('eklektik_stats_daily')
                ->orderBy('synced_at', 'desc')
                ->first();

            $isRecent = $lastSync ? $lastSync->synced_at > now()->subHours(24) : false;
            $totalRecords = \DB::table('eklektik_stats_daily')->count();
            
            // Déterminer le statut global
            $globalStatus = 'danger'; // Par défaut
            if ($isRecent && $totalRecords > 0) {
                $globalStatus = 'healthy';
            } else if ($totalRecords > 0) {
                $globalStatus = 'warning';
            }
            
            $status = [
                'status' => $globalStatus,
                'last_sync' => $lastSync ? $lastSync->synced_at : null,
                'is_recent' => $isRecent,
                'total_records' => $totalRecords,
                'date_range' => [
                    'first' => \DB::table('eklektik_stats_daily')->min('date'),
                    'last' => \DB::table('eklektik_stats_daily')->max('date')
                ],
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
                        ->count(),
                    'total_ca_bigdeal' => \DB::table('eklektik_stats_daily')
                        ->where('operator', $operator)
                        ->sum('ca_bigdeal')
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération du statut de synchronisation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vider le cache Eklektik
     */
    public function clearCache()
    {
        try {
            $clearedCount = $this->cacheService->clearCache();

            return response()->json([
                'success' => true,
                'message' => "Cache vidé avec succès ($clearedCount clés supprimées)"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du vidage du cache',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

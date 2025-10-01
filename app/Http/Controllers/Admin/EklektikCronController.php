<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EklektikCronConfig;
use App\Services\EklektikKPIOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class EklektikCronController extends Controller
{
    /**
     * Afficher la page de configuration du cron
     */
    public function index()
    {
        $configs = EklektikCronConfig::getAllConfigs();
        $cronStatus = $this->getCronStatus();
        $lastExecution = $this->getLastExecution();
        $statistics = $this->getCronStatistics();

        return view('admin.eklektik-cron', compact(
            'configs',
            'cronStatus',
            'lastExecution',
            'statistics'
        ));
    }

    /**
     * Mettre à jour la configuration du cron
     */
    public function updateConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cron_enabled' => 'required|in:true,false,1,0,on,off',
            'cron_schedule' => 'required|string|min:5|max:20',
            'cron_operators' => 'required|array',
            'cron_retention_days' => 'required|integer|min:1|max:365',
            'cron_notification_email' => 'nullable|email',
            'cron_error_email' => 'nullable|email',
            'cron_batch_size' => 'required|integer|min:100|max:10000',
            'cron_timeout' => 'required|integer|min:60|max:3600'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = auth()->id();

            // Valider la planification cron
            if (!EklektikCronConfig::validateCronSchedule($request->cron_schedule)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format de planification cron invalide'
                ], 422);
            }

            // Mettre à jour les configurations
            $cronEnabled = in_array($request->cron_enabled, ['true', '1', 'on', true, 1]);
            EklektikCronConfig::setConfig(
                EklektikCronConfig::CRON_ENABLED,
                $cronEnabled ? 'true' : 'false',
                'Activer/désactiver le cron Eklektik',
                $userId
            );

            EklektikCronConfig::setConfig(
                EklektikCronConfig::CRON_SCHEDULE,
                $request->cron_schedule,
                'Planification du cron (format cron)',
                $userId
            );

            EklektikCronConfig::setConfig(
                EklektikCronConfig::CRON_OPERATORS,
                json_encode($request->cron_operators),
                'Opérateurs à traiter par le cron',
                $userId
            );

            EklektikCronConfig::setConfig(
                EklektikCronConfig::CRON_RETENTION_DAYS,
                $request->cron_retention_days,
                'Nombre de jours de rétention des données de cache',
                $userId
            );

            EklektikCronConfig::setConfig(
                EklektikCronConfig::CRON_NOTIFICATION_EMAIL,
                $request->cron_notification_email ?? '',
                'Email pour les notifications de succès',
                $userId
            );

            EklektikCronConfig::setConfig(
                EklektikCronConfig::CRON_ERROR_EMAIL,
                $request->cron_error_email ?? '',
                'Email pour les notifications d\'erreur',
                $userId
            );

            EklektikCronConfig::setConfig(
                EklektikCronConfig::CRON_BATCH_SIZE,
                $request->cron_batch_size,
                'Taille des lots de traitement',
                $userId
            );

            EklektikCronConfig::setConfig(
                EklektikCronConfig::CRON_TIMEOUT,
                $request->cron_timeout,
                'Timeout en secondes pour le traitement',
                $userId
            );

            Log::info('🔧 Configuration du cron Eklektik mise à jour', [
                'user_id' => $userId,
                'config' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration mise à jour avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur mise à jour configuration cron', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la configuration'
            ], 500);
        }
    }

    /**
     * Tester la configuration du cron
     */
    public function testCron(Request $request)
    {
        try {
            $date = $request->get('date', now()->subDay()->format('Y-m-d'));
            $operator = $request->get('operator', 'ALL');

            Log::info('🧪 Test du cron Eklektik', [
                'date' => $date,
                'operator' => $operator,
                'user_id' => auth()->id()
            ]);

            $startTime = microtime(true);

            // Exécuter la commande de mise à jour
            Artisan::call('eklektik:update-daily-kpis', [
                '--date' => $date,
                '--operator' => $operator
            ]);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Test du cron exécuté avec succès',
                'duration' => $duration,
                'output' => $output
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur test cron', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test du cron: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exécuter le cron manuellement
     */
    public function runCron(Request $request)
    {
        try {
            $operators = EklektikCronConfig::getCronOperators();
            $date = now()->subDay()->format('Y-m-d');

            Log::info('🚀 Exécution manuelle du cron Eklektik', [
                'date' => $date,
                'operators' => $operators,
                'user_id' => auth()->id()
            ]);

            $startTime = microtime(true);
            $results = [];

            foreach ($operators as $operator) {
                $operatorStartTime = microtime(true);
                
                Artisan::call('eklektik:update-daily-kpis', [
                    '--date' => $date,
                    '--operator' => $operator
                ]);

                $operatorEndTime = microtime(true);
                $operatorDuration = round($operatorEndTime - $operatorStartTime, 2);

                $results[] = [
                    'operator' => $operator,
                    'duration' => $operatorDuration,
                    'success' => true
                ];
            }

            $endTime = microtime(true);
            $totalDuration = round($endTime - $startTime, 2);

            return response()->json([
                'success' => true,
                'message' => 'Cron exécuté avec succès',
                'total_duration' => $totalDuration,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur exécution manuelle cron', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution du cron: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut du cron
     */
    public function getCronStatus()
    {
        return [
            'enabled' => EklektikCronConfig::isCronEnabled(),
            'schedule' => EklektikCronConfig::getCronSchedule(),
            'operators' => EklektikCronConfig::getCronOperators(),
            'next_execution' => $this->calculateNextExecution()
        ];
    }

    /**
     * Obtenir les statistiques du cron (API)
     */
    public function getStatistics()
    {
        try {
            $statistics = $this->getCronStatistics();
            $cronStatus = $this->getCronStatus();
            
            return response()->json([
                'success' => true,
                'data' => array_merge($statistics, [
                    'next_execution' => $cronStatus['next_execution']
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération statistiques', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Obtenir la configuration du cron (API)
     */
    public function getConfig()
    {
        try {
            $configs = EklektikCronConfig::getAllConfigs();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'cron_enabled' => $configs->get('cron_enabled', 'true'),
                    'cron_schedule' => $configs->get('cron_schedule', '0 2 * * *'),
                    'cron_operators' => $configs->get('cron_operators', '["ALL"]'),
                    'cron_retention_days' => $configs->get('cron_retention_days', '90'),
                    'cron_notification_email' => $configs->get('cron_notification_email', ''),
                    'cron_error_email' => $configs->get('cron_error_email', ''),
                    'cron_batch_size' => $configs->get('cron_batch_size', '1000'),
                    'cron_timeout' => $configs->get('cron_timeout', '300'),
                    'enabled' => EklektikCronConfig::isCronEnabled()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération configuration', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la configuration'
            ], 500);
        }
    }

    /**
     * Obtenir la dernière exécution
     */
    private function getLastExecution()
    {
        // Récupérer la dernière exécution depuis les logs ou la base de données
        $lastExecution = \DB::table('eklektik_notifications_tracking')
            ->orderBy('processed_at', 'desc')
            ->first();

        return $lastExecution ? [
            'date' => $lastExecution->processed_at,
            'batch_id' => $lastExecution->processing_batch_id,
            'notifications_processed' => \DB::table('eklektik_notifications_tracking')
                ->where('processing_batch_id', $lastExecution->processing_batch_id)
                ->count()
        ] : null;
    }

    /**
     * Obtenir les statistiques du cron
     */
    private function getCronStatistics()
    {
        $optimizer = new EklektikKPIOptimizer();
        $stats = $optimizer->getProcessingStats();

        return [
            'total_processed' => $stats['total_processed'],
            'kpi_updated' => $stats['kpis_updated_count'],
            'unique_batches' => $stats['unique_batches_count'],
            'last_processed' => $stats['last_processing_update'],
            'cache_entries' => \DB::table('eklektik_kpis_cache')->count(),
            'tracking_entries' => \DB::table('eklektik_notifications_tracking')->count()
        ];
    }

    /**
     * Calculer la prochaine exécution
     */
    private function calculateNextExecution()
    {
        $schedule = EklektikCronConfig::getConfig(EklektikCronConfig::CRON_SCHEDULE, '0 2 * * *');
        
        // Logique simple pour calculer la prochaine exécution
        // Dans un vrai projet, on utiliserait une librairie comme cron-expression
        if ($schedule === '0 2 * * *') {
            $next = now()->addDay()->setHour(2)->setMinute(0)->setSecond(0);
            if ($next->isPast()) {
                $next = $next->addDay();
            }
            return $next;
        }

        return 'Calcul non disponible';
    }

    /**
     * Réinitialiser la configuration par défaut
     */
    public function resetToDefault()
    {
        try {
            EklektikCronConfig::initializeDefaultConfigs(auth()->id());

            Log::info('🔄 Configuration cron réinitialisée', [
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration réinitialisée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur réinitialisation configuration', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation'
            ], 500);
        }
    }
}
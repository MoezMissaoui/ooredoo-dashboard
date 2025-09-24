<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EklektikAlertService
{
    /**
     * Vérifier le statut des synchronisations et envoyer des alertes si nécessaire
     */
    public function checkSyncStatus()
    {
        $alerts = [];

        // Vérifier la dernière synchronisation
        $lastSync = DB::table('eklektik_stats_daily')
            ->orderBy('synced_at', 'desc')
            ->first();

        if (!$lastSync) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Aucune synchronisation Eklektik trouvée',
                'action' => 'Vérifier la configuration et lancer une synchronisation manuelle'
            ];
        } else {
            $lastSyncTime = Carbon::parse($lastSync->synced_at);
            $hoursSinceLastSync = $lastSyncTime->diffInHours(now());

            if ($hoursSinceLastSync > 48) {
                $alerts[] = [
                    'type' => 'critical',
                    'message' => "Dernière synchronisation il y a {$hoursSinceLastSync} heures",
                    'action' => 'Lancer une synchronisation manuelle immédiatement'
                ];
            } elseif ($hoursSinceLastSync > 24) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Dernière synchronisation il y a {$hoursSinceLastSync} heures",
                    'action' => 'Vérifier le statut de la synchronisation'
                ];
            }
        }

        // Vérifier les données par opérateur
        $operators = ['TT', 'Orange', 'Taraji'];
        foreach ($operators as $operator) {
            $operatorData = DB::table('eklektik_stats_daily')
                ->where('operator', $operator)
                ->orderBy('synced_at', 'desc')
                ->first();

            if (!$operatorData) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Aucune donnée trouvée pour l'opérateur $operator",
                    'action' => "Vérifier la configuration de l'opérateur $operator"
                ];
            } else {
                $operatorSyncTime = Carbon::parse($operatorData->synced_at);
                $hoursSinceOperatorSync = $operatorSyncTime->diffInHours(now());

                if ($hoursSinceOperatorSync > 48) {
                    $alerts[] = [
                        'type' => 'warning',
                        'message' => "Données $operator non synchronisées depuis {$hoursSinceOperatorSync} heures",
                        'action' => "Vérifier la configuration de l'opérateur $operator"
                    ];
                }
            }
        }

        // Vérifier la cohérence des données
        $inconsistentData = $this->checkDataConsistency();
        if (!empty($inconsistentData)) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Données incohérentes détectées',
                'details' => $inconsistentData,
                'action' => 'Vérifier les calculs de partage des revenus'
            ];
        }

        return $alerts;
    }

    /**
     * Vérifier la cohérence des données
     */
    private function checkDataConsistency()
    {
        $inconsistencies = [];

        // Vérifier que la somme des CA = Montant HT
        $inconsistentRecords = DB::table('eklektik_stats_daily')
            ->whereRaw('ABS((ca_operateur + ca_agregateur + ca_bigdeal) - montant_total_ht) > 0.01')
            ->get();

        if ($inconsistentRecords->count() > 0) {
            $inconsistencies[] = "{$inconsistentRecords->count()} enregistrements avec des totaux CA incohérents";
        }

        // Vérifier les taux de partage
        $invalidRates = DB::table('eklektik_stats_daily')
            ->whereRaw('(part_operateur + part_agregateur + part_bigdeal) != 100')
            ->get();

        if ($invalidRates->count() > 0) {
            $inconsistencies[] = "{$invalidRates->count()} enregistrements avec des taux de partage invalides";
        }

        return $inconsistencies;
    }

    /**
     * Envoyer une alerte par email
     */
    public function sendAlert($alert)
    {
        try {
            // Ici on pourrait envoyer un email, mais pour l'instant on log juste
            Log::warning('🚨 [EKLEKTIK ALERT] ' . $alert['message'], [
                'type' => $alert['type'],
                'action' => $alert['action'] ?? null,
                'details' => $alert['details'] ?? null,
                'timestamp' => now()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'alerte Eklektik: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Traiter toutes les alertes
     */
    public function processAlerts()
    {
        $alerts = $this->checkSyncStatus();
        $processed = 0;

        foreach ($alerts as $alert) {
            if ($this->sendAlert($alert)) {
                $processed++;
            }
        }

        return [
            'total_alerts' => count($alerts),
            'processed' => $processed,
            'alerts' => $alerts
        ];
    }

    /**
     * Obtenir le statut de santé du système Eklektik
     */
    public function getSystemHealth()
    {
        $lastSync = DB::table('eklektik_stats_daily')
            ->orderBy('synced_at', 'desc')
            ->first();

        $health = [
            'status' => 'healthy',
            'last_sync' => $lastSync ? $lastSync->synced_at : null,
            'hours_since_sync' => $lastSync ? Carbon::parse($lastSync->synced_at)->diffInHours(now()) : null,
            'total_records' => DB::table('eklektik_stats_daily')->count(),
            'operators_status' => [],
            'alerts' => []
        ];

        // Vérifier le statut par opérateur
        $operators = ['TT', 'Orange', 'Taraji'];
        foreach ($operators as $operator) {
            $operatorData = DB::table('eklektik_stats_daily')
                ->where('operator', $operator)
                ->orderBy('synced_at', 'desc')
                ->first();

            $health['operators_status'][$operator] = [
                'has_data' => $operatorData !== null,
                'last_sync' => $operatorData ? $operatorData->synced_at : null,
                'records_count' => DB::table('eklektik_stats_daily')->where('operator', $operator)->count(),
                'total_ca_bigdeal' => DB::table('eklektik_stats_daily')->where('operator', $operator)->sum('ca_bigdeal')
            ];
        }

        // Déterminer le statut global
        if (!$lastSync) {
            $health['status'] = 'critical';
        } elseif ($health['hours_since_sync'] > 48) {
            $health['status'] = 'critical';
        } elseif ($health['hours_since_sync'] > 24) {
            $health['status'] = 'warning';
        }

        // Vérifier les alertes
        $alerts = $this->checkSyncStatus();
        $health['alerts'] = $alerts;

        return $health;
    }

    /**
     * Créer un rapport de santé
     */
    public function generateHealthReport()
    {
        $health = $this->getSystemHealth();
        $report = [
            'timestamp' => now(),
            'status' => $health['status'],
            'summary' => [
                'last_sync' => $health['last_sync'],
                'hours_since_sync' => $health['hours_since_sync'],
                'total_records' => $health['total_records'],
                'operators_with_data' => count(array_filter($health['operators_status'], function($op) {
                    return $op['has_data'];
                }))
            ],
            'operators' => $health['operators_status'],
            'alerts' => $health['alerts'],
            'recommendations' => $this->getRecommendations($health)
        ];

        return $report;
    }

    /**
     * Obtenir des recommandations basées sur le statut
     */
    private function getRecommendations($health)
    {
        $recommendations = [];

        if ($health['status'] === 'critical') {
            $recommendations[] = 'Lancer une synchronisation manuelle immédiatement';
            $recommendations[] = 'Vérifier la configuration des accès Eklektik';
        }

        if ($health['hours_since_sync'] > 24) {
            $recommendations[] = 'Vérifier le scheduler Laravel';
            $recommendations[] = 'Contrôler les logs de synchronisation';
        }

        $operatorsWithoutData = array_filter($health['operators_status'], function($op) {
            return !$op['has_data'];
        });

        if (!empty($operatorsWithoutData)) {
            $recommendations[] = 'Vérifier la configuration des opérateurs sans données: ' . implode(', ', array_keys($operatorsWithoutData));
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Système en bon état - Aucune action requise';
        }

        return $recommendations;
    }
}


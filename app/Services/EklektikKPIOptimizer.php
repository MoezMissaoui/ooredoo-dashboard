<?php

namespace App\Services;

use App\Models\EklektikKPICache;
use App\Models\EklektikTransactionTracking;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EklektikKPIOptimizer
{
    /**
     * Initialiser les KPIs pour une période donnée
     */
    public function initializeKPIs($startDate, $endDate, $operator = 'ALL')
    {
        Log::info('🚀 [EKLEKTIK KPI] Initialisation des KPIs', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'operator' => $operator
        ]);

        $currentDate = $startDate;
        $processedDays = 0;
        
        while ($currentDate <= $endDate) {
            $this->processDailyKPIs($currentDate, $operator);
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            $processedDays++;
            
            // Log du progrès tous les 10 jours
            if ($processedDays % 10 === 0) {
                Log::info("📊 [EKLEKTIK KPI] Progrès: {$processedDays} jours traités");
            }
        }

        Log::info('✅ [EKLEKTIK KPI] Initialisation terminée', [
            'total_days' => $processedDays
        ]);
    }

    /**
     * Mettre à jour les KPIs du jour
     */
    public function updateDailyKPIs($date, $operator = 'ALL')
    {
        Log::info('📊 [EKLEKTIK KPI] Mise à jour quotidienne', [
            'date' => $date,
            'operator' => $operator
        ]);

        $this->processDailyKPIs($date, $operator);
    }

    /**
     * Récupérer les KPIs en temps réel
     */
    public function getRealTimeKPIs($startDate, $endDate, $operator = 'ALL')
    {
        $cacheKey = "eklektik_kpis_realtime_{$startDate}_{$endDate}_{$operator}";
        
        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate, $operator) {
            // Récupérer les totaux mis en cache
            $cachedKPIs = $this->getCachedKPIs($startDate, $endDate, $operator);
            
            // Ajouter les transactions du jour en cours
            $todayKPIs = $this->getTodayKPIs($operator);
            
            // Fusionner les données
            return $this->mergeKPIs($cachedKPIs, $todayKPIs);
        });
    }

    /**
     * Récupérer les statistiques de traitement
     */
    public function getProcessingStats($startDate = null, $endDate = null)
    {
        $query = EklektikTransactionTracking::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        
        $totalProcessed = $query->count();
        $kpisUpdated = $query->where('kpi_updated', true)->count();
        $uniqueBatches = $query->distinct('processing_batch_id')->count();
        $lastUpdate = EklektikTransactionTracking::max('processed_at');

        return [
            'total_processed' => $totalProcessed,
            'kpis_updated_count' => $kpisUpdated,
            'unique_batches_count' => $uniqueBatches,
            'last_processing_update' => $lastUpdate ? $lastUpdate->toDateTimeString() : 'N/A'
        ];
    }

    /**
     * Traiter les KPIs d'une journée
     */
    private function processDailyKPIs($date, $operator)
    {
        $batchId = "batch_" . $date . "_" . $operator . "_" . time();
        
        // Récupérer les transactions non traitées
        $transactions = $this->getUntrackedTransactions($date, $operator);
        
        if ($transactions->isEmpty()) {
            Log::info('ℹ️ [EKLEKTIK KPI] Aucune nouvelle transaction à traiter', [
                'date' => $date,
                'operator' => $operator
            ]);
            return;
        }

        // Calculer les KPIs
        $kpis = $this->calculateKPIsFromTransactions($transactions);
        
        // Mettre à jour le cache
        $this->updateKPICache($date, $operator, $kpis);
        
        // Marquer les transactions comme traitées
        $this->markTransactionsAsProcessed($transactions, $batchId);
        
        Log::info('✅ [EKLEKTIK KPI] KPIs mis à jour', [
            'date' => $date,
            'operator' => $operator,
            'transactions_count' => $transactions->count(),
            'batch_id' => $batchId
        ]);
    }

    /**
     * Récupérer les transactions non traitées
     */
    private function getUntrackedTransactions($date, $operator)
    {
        return DB::table('transactions_history')
            ->leftJoin('eklektik_transactions_tracking', 'transactions_history.transaction_history_id', '=', 'eklektik_transactions_tracking.transaction_id')
            ->whereDate('transactions_history.created_at', $date)
            ->where(function($q) {
                $q->where('transactions_history.status', 'LIKE', '%ORANGE%')
                  ->orWhere('transactions_history.status', 'LIKE', '%TT%')
                  ->orWhere('transactions_history.status', 'LIKE', '%TIMWE%')
                  ->orWhere('transactions_history.status', 'LIKE', '%TARAJI%');
            })
            ->where(function($q) use ($operator) {
                if ($operator !== 'ALL') {
                    $q->where('transactions_history.status', 'LIKE', "%{$operator}%");
                }
            })
            ->whereNull('eklektik_transactions_tracking.transaction_id')
            ->select('transactions_history.*')
            ->get();
    }

    /**
     * Calculer les KPIs à partir des transactions
     */
    private function calculateKPIsFromTransactions($transactions)
    {
        $kpis = [
            'billing_rate' => 0,
            'revenue' => 0,
            'active_subscriptions' => 0,
            'new_subscriptions' => 0,
            'unsubscriptions' => 0,
            'billed_clients' => 0
        ];

        $subCount = 0;
        $renewCount = 0;
        $chargeCount = 0;
        $unsubCount = 0;
        $totalRevenue = 0;
        $billedClients = [];

        foreach ($transactions as $transaction) {
            $parsed = $this->parseTransactionResult($transaction->result, $transaction->status);
            if (!$parsed) continue;

            $action = $parsed['action'] ?? '';
            $amount = floatval($parsed['amount'] ?? 0);
            $subscriptionId = $parsed['subscriptionid'] ?? $transaction->order_id;

            switch ($action) {
                case 'SUB':
                case 'SUBSCRIPTION':
                    $subCount++;
                    break;
                case 'RENEW':
                case 'RENEWED':
                    $renewCount++;
                    $totalRevenue += $amount;
                    break;
                case 'CHARGE':
                case 'CHARGED':
                    $chargeCount++;
                    $totalRevenue += $amount;
                    break;
                case 'UNSUB':
                case 'UNSUBSCRIPTION':
                    $unsubCount++;
                    break;
            }

            if (in_array($action, ['RENEW', 'RENEWED', 'CHARGE', 'CHARGED']) && $subscriptionId) {
                $billedClients[$subscriptionId] = true;
            }
        }

        // Calculer les KPIs
        $totalBilling = $renewCount + $chargeCount;
        $totalSubscriptions = $subCount + $totalBilling;
        
        $kpis['billing_rate'] = $totalSubscriptions > 0 ? round(($totalBilling / $totalSubscriptions) * 100, 2) : 0;
        $kpis['revenue'] = $totalRevenue;
        $kpis['active_subscriptions'] = max(0, $subCount - $unsubCount);
        $kpis['new_subscriptions'] = $subCount;
        $kpis['unsubscriptions'] = $unsubCount;
        $kpis['billed_clients'] = count($billedClients);

        return $kpis;
    }

    /**
     * Parser le résultat JSON d'une transaction
     */
    private function parseTransactionResult($result, $status)
    {
        if (empty($result)) return null;

        try {
            $data = json_decode($result, true);
            if (!$data) return null;

            // Analyser le statut pour déterminer l'action
            $action = $this->mapStatusToAction($status);
            
            // Extraire les informations pertinentes
            $parsed = [
                'action' => $action,
                'amount' => $this->extractAmount($data),
                'subscriptionid' => $this->extractSubscriptionId($data),
                'msisdn' => $this->extractMsisdn($data),
                'operator' => $this->extractOperator($status)
            ];

            return $parsed;

        } catch (\Exception $e) {
            Log::warning('Erreur parsing transaction result', [
                'error' => $e->getMessage(),
                'status' => $status,
                'result' => substr($result, 0, 100)
            ]);
            return null;
        }
    }

    /**
     * Mapper le statut vers une action
     */
    private function mapStatusToAction($status)
    {
        $statusMap = [
            'ORANGE_CHECK_USER' => 'SUB',
            'ORANGE_GET_SUBSCRIPTION' => 'SUB',
            'TT_CHECK_USER' => 'SUB',
            'TIMWE_SEND_SMS' => 'SUB',
            'TIMWE_RENEWED_NOTIF' => 'RENEW',
            'TIMWE_CHARGE_DELIVERED' => 'CHARGE',
            'TIMWE_CHECK_STATUS' => 'SUB',
            'TIMWE_REQUEST_SUBSCRIPTION' => 'SUB',
            'OOREDOO_PAYMENT_OFFLINE_INIT' => 'SUB'
        ];

        return $statusMap[$status] ?? 'SUB';
    }

    /**
     * Extraire le montant du JSON
     */
    private function extractAmount($data)
    {
        // Chercher dans différentes structures possibles
        $amountFields = ['amount', 'price', 'cost', 'value', 'total'];
        
        foreach ($amountFields as $field) {
            if (isset($data[$field])) {
                return floatval($data[$field]);
            }
        }

        // Chercher dans des sous-objets
        if (isset($data['user']['amount'])) {
            return floatval($data['user']['amount']);
        }

        return 0;
    }

    /**
     * Extraire l'ID de souscription
     */
    private function extractSubscriptionId($data)
    {
        $idFields = ['subscription_id', 'subscriptionid', 'id', 'user_id'];
        
        foreach ($idFields as $field) {
            if (isset($data[$field])) {
                return $data[$field];
            }
        }

        if (isset($data['user']['id'])) {
            return $data['user']['id'];
        }

        return null;
    }

    /**
     * Extraire le MSISDN
     */
    private function extractMsisdn($data)
    {
        $msisdnFields = ['msisdn', 'phone', 'telephone', 'mobile'];
        
        foreach ($msisdnFields as $field) {
            if (isset($data[$field])) {
                return $data[$field];
            }
        }

        if (isset($data['user']['msisdn'])) {
            return $data['user']['msisdn'];
        }

        return null;
    }

    /**
     * Extraire l'opérateur du statut
     */
    private function extractOperator($status)
    {
        if (strpos($status, 'ORANGE') !== false) return 'Orange';
        if (strpos($status, 'TT') !== false) return 'TT';
        if (strpos($status, 'TIMWE') !== false) return 'Timwe';
        if (strpos($status, 'OOREDOO') !== false) return 'Ooredoo';
        if (strpos($status, 'TARAJI') !== false) return 'Taraji';
        
        return 'Unknown';
    }

    /**
     * Mettre à jour le cache des KPIs
     */
    private function updateKPICache($date, $operator, $kpis)
    {
        foreach ($kpis as $kpiType => $value) {
            EklektikKPICache::updateOrCreate(
                [
                    'date' => $date,
                    'operator' => $operator,
                    'kpi_type' => $kpiType
                ],
                [
                    'total_value' => $value,
                    'daily_value' => $value,
                    'notifications_count' => 1,
                    'last_updated' => now()
                ]
            );
        }
    }

    /**
     * Marquer les transactions comme traitées
     */
    private function markTransactionsAsProcessed($transactions, $batchId)
    {
        $trackingData = $transactions->map(function ($transaction) use ($batchId) {
            return [
                'transaction_id' => $transaction->transaction_history_id,
                'processed_at' => now(),
                'kpi_updated' => true,
                'processing_batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now()
            ];
        })->toArray();

        EklektikTransactionTracking::insert($trackingData);
    }

    /**
     * Récupérer les KPIs mis en cache
     */
    private function getCachedKPIs($startDate, $endDate, $operator)
    {
        return EklektikKPICache::whereBetween('date', [$startDate, $endDate])
            ->where('operator', $operator)
            ->get()
            ->groupBy('kpi_type')
            ->map(function ($items) {
                return $items->sum('total_value');
            })
            ->toArray();
    }

    /**
     * Récupérer les KPIs du jour en cours
     */
    private function getTodayKPIs($operator)
    {
        $today = now()->format('Y-m-d');
        $transactions = $this->getUntrackedTransactions($today, $operator);
        return $this->calculateKPIsFromTransactions($transactions);
    }

    /**
     * Fusionner les KPIs
     */
    private function mergeKPIs($cachedKPIs, $todayKPIs)
    {
        $result = [];
        $allKpiTypes = EklektikKPICache::getKPITypes();
        
        foreach ($allKpiTypes as $kpiType) {
            $cachedValue = $cachedKPIs[$kpiType] ?? 0;
            $todayValue = $todayKPIs[$kpiType] ?? 0;
            $result[$kpiType] = $cachedValue + $todayValue;
        }
        
        return $result;
    }
}
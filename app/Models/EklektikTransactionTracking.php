<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EklektikTransactionTracking extends Model
{
    use HasFactory;

    protected $table = 'eklektik_transactions_tracking';

    protected $fillable = [
        'transaction_id',
        'processed_at',
        'kpi_updated',
        'processing_batch_id',
        'processing_metadata'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'kpi_updated' => 'boolean',
        'processing_metadata' => 'array'
    ];

    /**
     * Relation vers la transaction
     */
    public function transaction()
    {
        return $this->belongsTo(\App\Models\TransactionHistory::class, 'transaction_id', 'transaction_history_id');
    }

    /**
     * Récupérer les transactions traitées pour une période
     */
    public static function getProcessedTransactions($startDate, $endDate)
    {
        return self::whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with('transaction')
            ->get();
    }

    /**
     * Récupérer les statistiques de traitement
     */
    public static function getProcessingStats($startDate = null, $endDate = null)
    {
        $query = self::query();
        
        if ($startDate && $endDate) {
            $query->whereBetween('processed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        
        return [
            'total_processed' => $query->count(),
            'kpis_updated' => $query->where('kpi_updated', true)->count(),
            'unique_batches' => $query->distinct('processing_batch_id')->count(),
            'last_processed' => $query->max('processed_at')
        ];
    }

    /**
     * Marquer une transaction comme traitée
     */
    public static function markAsProcessed($transactionId, $batchId, $metadata = null)
    {
        return self::create([
            'transaction_id' => $transactionId,
            'processed_at' => now(),
            'kpi_updated' => true,
            'processing_batch_id' => $batchId,
            'processing_metadata' => $metadata
        ]);
    }

    /**
     * Vérifier si une transaction a été traitée
     */
    public static function isProcessed($transactionId)
    {
        return self::where('transaction_id', $transactionId)->exists();
    }
}
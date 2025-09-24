<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionHistory extends Model
{
    use HasFactory;

    protected $table = 'transactions_history';
    protected $primaryKey = 'transaction_history_id';

    protected $fillable = [
        'client_id',
        'tarif_id',
        'order_id',
        'reference',
        'status',
        'result',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'result' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relation vers le client
     */
    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class, 'client_id');
    }

    /**
     * Relation vers le tarif
     */
    public function tarif()
    {
        return $this->belongsTo(\App\Models\Tarif::class, 'tarif_id');
    }

    /**
     * Relation vers le suivi Eklektik
     */
    public function eklektikTracking()
    {
        return $this->hasOne(EklektikTransactionTracking::class, 'transaction_id', 'transaction_history_id');
    }

    /**
     * Vérifier si c'est une transaction Eklektik
     */
    public function isEklektikTransaction()
    {
        $eklektikStatuses = [
            'ORANGE_CHECK_USER',
            'ORANGE_GET_SUBSCRIPTION',
            'TT_CHECK_USER',
            'TIMWE_SEND_SMS',
            'TIMWE_RENEWED_NOTIF',
            'TIMWE_CHARGE_DELIVERED',
            'TIMWE_CHECK_STATUS',
            'TIMWE_REQUEST_SUBSCRIPTION',
            'OOREDOO_PAYMENT_OFFLINE_INIT'
        ];

        return in_array($this->status, $eklektikStatuses);
    }

    /**
     * Obtenir l'opérateur de la transaction
     */
    public function getOperator()
    {
        if (strpos($this->status, 'ORANGE') !== false) return 'Orange';
        if (strpos($this->status, 'TT') !== false) return 'TT';
        if (strpos($this->status, 'TIMWE') !== false) return 'Timwe';
        if (strpos($this->status, 'OOREDOO') !== false) return 'Ooredoo';
        if (strpos($this->status, 'TARAJI') !== false) return 'Taraji';
        
        return 'Unknown';
    }

    /**
     * Obtenir l'action de la transaction
     */
    public function getAction()
    {
        $actionMap = [
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

        return $actionMap[$this->status] ?? 'SUB';
    }

    /**
     * Parser le résultat JSON
     */
    public function getParsedResult()
    {
        if (empty($this->result)) return null;

        try {
            $data = is_array($this->result) ? $this->result : json_decode($this->result, true);
            
            if (!$data) return null;

            return [
                'action' => $this->getAction(),
                'amount' => $this->extractAmount($data),
                'subscription_id' => $this->extractSubscriptionId($data),
                'msisdn' => $this->extractMsisdn($data),
                'operator' => $this->getOperator()
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extraire le montant du JSON
     */
    private function extractAmount($data)
    {
        $amountFields = ['amount', 'price', 'cost', 'value', 'total'];
        
        foreach ($amountFields as $field) {
            if (isset($data[$field])) {
                return floatval($data[$field]);
            }
        }

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

        return $this->order_id;
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
     * Scope pour les transactions Eklektik
     */
    public function scopeEklektik($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'LIKE', '%ORANGE%')
              ->orWhere('status', 'LIKE', '%TT%')
              ->orWhere('status', 'LIKE', '%TIMWE%')
              ->orWhere('status', 'LIKE', '%TARAJI%')
              ->orWhere('status', 'LIKE', '%OOREDOO%');
        });
    }

    /**
     * Scope pour un opérateur spécifique
     */
    public function scopeForOperator($query, $operator)
    {
        if ($operator === 'ALL') {
            return $query->eklektik();
        }

        return $query->where('status', 'LIKE', "%{$operator}%");
    }

    /**
     * Scope pour une période
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EklektikStatsDaily extends Model
{
    use HasFactory;

    protected $table = 'eklektik_stats_daily';
    
    protected $fillable = [
        'date',
        'operator',
        'offre_id',
        'service_name',
        'offer_name',
        'offer_type',
        'new_subscriptions',
        'renewals',
        'charges',
        'unsubscriptions',
        'simchurn',
        'rev_simchurn_cents',
        'rev_simchurn_tnd',
        'nb_facturation',
        'revenu_ttc_local',
        'revenu_ttc_usd',
        'revenu_ttc_tnd',
        'montant_total_ht',
        'part_operateur',
        'part_agregateur',
        'part_bigdeal',
        'ca_operateur',
        'ca_agregateur',
        'ca_bigdeal',
        'active_subscribers',
        'revenue_cents',
        'billing_rate',
        'total_revenue',
        'average_price',
        'total_amount',
        'source',
        'synced_at'
    ];

    protected $casts = [
        'date' => 'date',
        'total_revenue_ttc' => 'decimal:2',
        'total_revenue_ht' => 'decimal:2',
        'ca_operateur' => 'decimal:2',
        'ca_agregateur' => 'decimal:2',
        'ca_bigdeal' => 'decimal:2',
        'billing_rate' => 'decimal:2',
        'bigdeal_share' => 'decimal:2'
    ];

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForOperator($query, $operator)
    {
        if ($operator === 'ALL') {
            return $query;
        }
        return $query->where('operator', $operator);
    }
}

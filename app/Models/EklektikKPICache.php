<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EklektikKPICache extends Model
{
    use HasFactory;

    protected $table = 'eklektik_kpis_cache';

    protected $fillable = [
        'date',
        'operator',
        'kpi_type',
        'total_value',
        'daily_value',
        'notifications_count',
        'last_updated'
    ];

    protected $casts = [
        'date' => 'date',
        'total_value' => 'decimal:2',
        'daily_value' => 'decimal:2',
        'last_updated' => 'datetime'
    ];

    // Constantes pour les types de KPI
    const KPI_BILLING_RATE = 'billing_rate';
    const KPI_REVENUE = 'revenue';
    const KPI_ACTIVE_SUBSCRIPTIONS = 'active_subscriptions';
    const KPI_NEW_SUBSCRIPTIONS = 'new_subscriptions';
    const KPI_UNSUBSCRIPTIONS = 'unsubscriptions';
    const KPI_BILLED_CLIENTS = 'billed_clients';

    public static function getKPITypes()
    {
        return [
            self::KPI_BILLING_RATE,
            self::KPI_REVENUE,
            self::KPI_ACTIVE_SUBSCRIPTIONS,
            self::KPI_NEW_SUBSCRIPTIONS,
            self::KPI_UNSUBSCRIPTIONS,
            self::KPI_BILLED_CLIENTS
        ];
    }

    /**
     * Récupérer les KPIs pour une période donnée
     */
    public static function getKPIsForPeriod($startDate, $endDate, $operator = 'ALL')
    {
        return self::whereBetween('date', [$startDate, $endDate])
            ->where('operator', $operator)
            ->get()
            ->groupBy('kpi_type')
            ->map(function ($items) {
                return $items->sum('total_value');
            })
            ->toArray();
    }

    /**
     * Récupérer les KPIs du jour
     */
    public static function getTodayKPIs($operator = 'ALL')
    {
        return self::where('date', now()->format('Y-m-d'))
            ->where('operator', $operator)
            ->get()
            ->keyBy('kpi_type')
            ->map(function ($item) {
                return $item->daily_value;
            })
            ->toArray();
    }

    /**
     * Mettre à jour ou créer un KPI
     */
    public static function updateOrCreateKPI($date, $operator, $kpiType, $value, $notificationsCount = 1)
    {
        return self::updateOrCreate(
            [
                'date' => $date,
                'operator' => $operator,
                'kpi_type' => $kpiType
            ],
            [
                'total_value' => $value,
                'daily_value' => $value,
                'notifications_count' => $notificationsCount,
                'last_updated' => now()
            ]
        );
    }
}
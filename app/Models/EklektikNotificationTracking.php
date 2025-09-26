<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EklektikNotificationTracking extends Model
{
    use HasFactory;

    protected $table = 'eklektik_notifications_tracking';

    protected $fillable = [
        'processed_at',
        'status',
        'details'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'details' => 'array'
    ];
}

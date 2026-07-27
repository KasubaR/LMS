<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'reference',
        'lenco_reference',
        'recipient_name',
        'recipient_phone',
        'operator',
        'amount',
        'currency',
        'status',
        'reason_for_failure',
        'raw_response',
        'initiated_by',
        'confirmed_at',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'confirmed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'reference',
        'order_id',
        'user_id',
        'provider',
        'provider_reference',
        'amount_minor',
        'currency',
        'payment_method',
        'status',
        'idempotency_key',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }
}

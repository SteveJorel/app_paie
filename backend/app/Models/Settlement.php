<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    protected $fillable = [
        'allocation_id',
        'supplier_id',
        'currency',
        'amount_minor',
        'status',
        'reference',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'settled_at' => 'datetime',
        ];
    }
}

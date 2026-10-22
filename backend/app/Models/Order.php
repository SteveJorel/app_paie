<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'user_id',
        'supplier_id',
        'description',
        'amount_minor',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
        ];
    }
}

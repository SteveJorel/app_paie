<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allocation extends Model
{
    protected $fillable = [
        'payment_id',
        'beneficiary_type',
        'beneficiary_id',
        'gross_amount_minor',
        'fees_minor',
        'net_amount_minor',
        'currency',
        'status',
        'commission_rule_id',
        'commission_rule_version',
    ];

    protected function casts(): array
    {
        return [
            'beneficiary_id' => 'integer',
            'gross_amount_minor' => 'integer',
            'fees_minor' => 'integer',
            'net_amount_minor' => 'integer',
            'commission_rule_version' => 'integer',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class CommissionRule extends Model
{
    use HasFactory;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_COMBINED = 'combined';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETIRED = 'retired';

    protected $fillable = [
        'supplier_id',
        'currency',
        'type',
        'percentage_bps',
        'fixed_minor',
        'valid_from',
        'valid_until',
        'version',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'percentage_bps' => 'integer',
            'fixed_minor' => 'integer',
            'version' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Pas de modification rétroactive : seuls le statut et la fin de validité peuvent changer.
        static::updating(function (CommissionRule $rule) {
            $forbidden = array_diff(array_keys($rule->getDirty()), ['status', 'valid_until', 'updated_at']);

            if ($forbidden !== []) {
                throw new LogicException('Une règle de commission est immuable : créez une nouvelle version.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Une règle de commission ne peut pas être supprimée.');
        });
    }
} 

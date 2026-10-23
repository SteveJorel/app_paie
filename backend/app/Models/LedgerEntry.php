<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'entry_type',
        'payment_id',
        'beneficiary_type',
        'beneficiary_id',
        'amount_minor',
        'currency',
    ];

    // Écritures comptables en ajout seulement.
    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Les écritures du journal financier ne peuvent pas être modifiées.');
        });

        static::deleting(function () {
            throw new LogicException('Les écritures du journal financier ne peuvent pas être supprimées.');
        });
    }
}

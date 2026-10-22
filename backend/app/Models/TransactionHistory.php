<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class TransactionHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'transaction_history';

    protected $fillable = [
        'payment_id',
        'previous_status',
        'new_status',
        'event_type',
        'event_reference',
        'actor_type',
        'actor_id',
    ];

    // Historique en ajout seulement : on ne réécrit jamais le passé.
    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException("L'historique des transactions ne peut pas être modifié.");
        });

        static::deleting(function () {
            throw new LogicException("L'historique des transactions ne peut pas être supprimé.");
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class Receipt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'receipt_number',
        'payment_id',
        'user_id',
        'receipt_type',
        'status',
        'snapshot',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    // Un reçu émis n'est jamais modifié ni supprimé : un remboursement produira un second document.
    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Un reçu émis ne peut pas être modifié.');
        });

        static::deleting(function () {
            throw new LogicException('Un reçu émis ne peut pas être supprimé.');
        });
    }
}

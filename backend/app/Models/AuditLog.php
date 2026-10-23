<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'actor_role',
        'action',
        'auditable_type',
        'auditable_id',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    // Journal d'audit en ajout seulement.
    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException("Le journal d'audit ne peut pas être modifié.");
        });

        static::deleting(function () {
            throw new LogicException("Le journal d'audit ne peut pas être supprimé.");
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'payment_id',
        'type',
        'channel',
        'payload',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}

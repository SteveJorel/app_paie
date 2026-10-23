<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;

final class NotificationDispatcher
{
    // Enregistre l'intention de notifier ; aucun canal réel (SMS/e-mail) n'est encore branché.
    public static function dispatch(?int $userId, ?int $paymentId, string $type, array $payload = []): NotificationLog
    {
        return NotificationLog::create([
            'user_id' => $userId,
            'payment_id' => $paymentId,
            'type' => $type,
            'channel' => 'in_app',
            'payload' => $payload,
            'status' => 'queued',
        ]);
    }
}

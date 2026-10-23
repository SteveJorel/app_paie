<?php

namespace App\Services\Audit;

use App\Models\AuditLog;

final class AuditLogger
{
    public static function record(
        ?int $actorId,
        ?string $actorRole,
        string $action,
        string $auditableType,
        ?int $auditableId,
        array $changes = [],
    ): void {
        AuditLog::create([
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'changes' => $changes,
        ]);
    }
}

<?php

namespace App\Observers;

use App\Models\CommissionRule;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;

class CommissionRuleObserver
{
    public function created(CommissionRule $rule): void
    {
        $actor = Auth::user();

        AuditLogger::record(
            actorId: $actor?->id,
            actorRole: $actor?->role,
            action: 'commission_rule.created',
            auditableType: CommissionRule::class,
            auditableId: $rule->id,
            changes: [
                'type' => $rule->type,
                'currency' => $rule->currency,
                'supplier_id' => $rule->supplier_id,
                'version' => $rule->version,
            ],
        );
    }

    public function updated(CommissionRule $rule): void
    {
        if (! $rule->wasChanged('status')) {
            return;
        }

        $actor = Auth::user();

        AuditLogger::record(
            actorId: $actor?->id,
            actorRole: $actor?->role,
            action: 'commission_rule.'.$rule->status,
            auditableType: CommissionRule::class,
            auditableId: $rule->id,
            changes: [
                'status' => [$rule->getOriginal('status'), $rule->status],
            ],
        );
    }
}

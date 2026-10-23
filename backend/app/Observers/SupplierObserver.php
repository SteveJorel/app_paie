<?php

namespace App\Observers;

use App\Models\Supplier;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Auth;

class SupplierObserver
{
    public function updated(Supplier $supplier): void
    {
        if (! $supplier->wasChanged('status')) {
            return;
        }

        $actor = Auth::user();

        AuditLogger::record(
            actorId: $actor?->id,
            actorRole: $actor?->role,
            action: 'supplier.'.$supplier->status,
            auditableType: Supplier::class,
            auditableId: $supplier->id,
            changes: [
                'status' => [$supplier->getOriginal('status'), $supplier->status],
            ],
        );
    }
}

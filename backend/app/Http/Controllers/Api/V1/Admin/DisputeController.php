<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\TransactionHistory;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisputeController extends Controller
{
    public function open(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($data, $request, $payment) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            abort_if($locked->status !== 'paid', 422, 'Seul un paiement confirmé peut être contesté.');

            $previousStatus = $locked->status;
            $locked->update(['status' => 'disputed']);

            TransactionHistory::create([
                'payment_id' => $locked->id,
                'previous_status' => $previousStatus,
                'new_status' => 'disputed',
                'event_type' => 'dispute.opened',
                'actor_type' => 'admin',
                'actor_id' => $request->user()->id,
            ]);

            AuditLogger::record(
                actorId: $request->user()->id,
                actorRole: $request->user()->role,
                action: 'dispute.opened',
                auditableType: Payment::class,
                auditableId: $locked->id,
                changes: ['reason' => $data['reason']],
            );

            NotificationDispatcher::dispatch($locked->user_id, $locked->id, 'dispute.opened', ['reason' => $data['reason']]);

            return response()->json(['data' => $locked->fresh()]);
        });
    }

    public function resolve(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'resolution' => ['required', 'string', 'in:rejected,upheld'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($data, $request, $payment) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            abort_if($locked->status !== 'disputed', 422, "Ce paiement n'est pas en contestation.");

            // "rejected" : la contestation est écartée, le paiement redevient payé.
            // "upheld" : la contestation est fondée ; le statut reste "disputed" jusqu'au
            // remboursement effectif via POST /admin/refunds (désormais accepté sur ce statut).
            $newStatus = $data['resolution'] === 'rejected' ? 'paid' : 'disputed';
            $previousStatus = $locked->status;
            $locked->update(['status' => $newStatus]);

            TransactionHistory::create([
                'payment_id' => $locked->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'event_type' => 'dispute.'.$data['resolution'],
                'actor_type' => 'admin',
                'actor_id' => $request->user()->id,
            ]);

            AuditLogger::record(
                actorId: $request->user()->id,
                actorRole: $request->user()->role,
                action: 'dispute.'.$data['resolution'],
                auditableType: Payment::class,
                auditableId: $locked->id,
                changes: ['note' => $data['note'] ?? null],
            );

            NotificationDispatcher::dispatch($locked->user_id, $locked->id, 'dispute.'.$data['resolution'], ['note' => $data['note'] ?? null]);

            return response()->json(['data' => $locked->fresh()]);
        });
    }
}

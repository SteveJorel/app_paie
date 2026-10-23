<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\TransactionHistory;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Payments\ReceiptIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($data, $request) {
            $payment = Payment::whereKey($data['payment_id'])->lockForUpdate()->firstOrFail();

            abort_if(
                ! in_array($payment->status, ['paid', 'disputed'], true),
                422,
                'Seul un paiement confirmé ou contesté peut être remboursé.'
            );
            abort_if(Refund::where('payment_id', $payment->id)->exists(), 422, 'Ce paiement a déjà fait l\'objet d\'un remboursement.');

            $refund = Refund::create([
                'payment_id' => $payment->id,
                'reference' => 'REF-'.Str::ulid(),
                'amount_minor' => $payment->amount_minor,
                'currency' => $payment->currency,
                'reason' => $data['reason'],
                'status' => 'completed',
                'requested_by' => $request->user()->id,
                'approved_by' => $request->user()->id,
                'requested_at' => now(),
                'approved_at' => now(),
                'completed_at' => now(),
            ]);

            $previousStatus = $payment->status;
            $payment->update(['status' => 'refunded']);

            TransactionHistory::create([
                'payment_id' => $payment->id,
                'previous_status' => $previousStatus,
                'new_status' => 'refunded',
                'event_type' => 'refund.completed',
                'event_reference' => $refund->reference,
                'actor_type' => 'admin',
                'actor_id' => $request->user()->id,
            ]);

            ReceiptIssuer::issueRefundFor($payment, $refund);

            AuditLogger::record(
                actorId: $request->user()->id,
                actorRole: $request->user()->role,
                action: 'refund.completed',
                auditableType: Payment::class,
                auditableId: $payment->id,
                changes: [
                    'refund_reference' => $refund->reference,
                    'amount_minor' => $refund->amount_minor,
                ],
            );

            NotificationDispatcher::dispatch($payment->user_id, $payment->id, 'refund.completed', [
                'refund_reference' => $refund->reference,
            ]);

            return response()->json(['data' => $refund], 201);
        });
    }
}

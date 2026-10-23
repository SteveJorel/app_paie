<?php

namespace App\Services\Ledger;

use App\Models\Allocation;
use App\Models\LedgerEntry;
use App\Models\Payment;

final class LedgerRecorder
{
    // Idempotent : un paiement déjà enregistré ne produit pas de doublon.
    public static function recordForPayment(Payment $payment): void
    {
        if (LedgerEntry::where('payment_id', $payment->id)->exists()) {
            return;
        }

        $allocations = Allocation::where('payment_id', $payment->id)->get();

        foreach ($allocations as $allocation) {
            LedgerEntry::create([
                'entry_type' => $allocation->beneficiary_type === 'company' ? 'commission' : 'settlement_due',
                'payment_id' => $payment->id,
                'beneficiary_type' => $allocation->beneficiary_type,
                'beneficiary_id' => $allocation->beneficiary_id,
                'amount_minor' => $allocation->net_amount_minor,
                'currency' => $allocation->currency,
            ]);
        }
    }
}

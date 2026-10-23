<?php

namespace App\Services\Payments;

use App\Models\Allocation;
use App\Models\Payment;
use App\Models\Settlement;
use Illuminate\Support\Str;

final class SettlementIssuer
{
    // Idempotent : appelée plusieurs fois pour le même paiement, ne crée qu'un seul reversement.
    public static function issueFor(Payment $payment): ?Settlement
    {
        $allocation = Allocation::where('payment_id', $payment->id)
            ->where('beneficiary_type', 'supplier')
            ->lockForUpdate()
            ->first();

        if ($allocation === null) {
            return null;
        }

        $existing = Settlement::where('allocation_id', $allocation->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $settlement = Settlement::create([
            'allocation_id' => $allocation->id,
            'supplier_id' => $allocation->beneficiary_id,
            'currency' => $allocation->currency,
            'amount_minor' => $allocation->net_amount_minor,
            'status' => 'pending',
            'reference' => 'STL-'.Str::ulid(),
        ]);

        $allocation->update(['status' => 'settlement_pending']);

        return $settlement;
    }
}

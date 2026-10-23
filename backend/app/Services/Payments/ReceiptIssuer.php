<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Supplier;
use App\Models\Refund;

final class ReceiptIssuer
{
    /**
     * Émet le reçu officiel d'un paiement confirmé. Sans effet si le reçu existe déjà.
     */
    public static function issueFor(Payment $payment): Receipt
    {
        $existing = Receipt::where('payment_id', $payment->id)->where('receipt_type', 'payment')->first();

        if ($existing) {
            return $existing;
        }

        $order = Order::findOrFail($payment->order_id);
        $supplier = Supplier::findOrFail($order->supplier_id);
        $issuedAt = $payment->confirmed_at ?? now();
        $number = sprintf('RC-%s-%08d', $issuedAt->format('Y'), $payment->id);

        return Receipt::create([
            'receipt_number' => $number,
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'receipt_type' => 'payment',
            'status' => 'issued',
            // Copie figée de tout ce qui est imprimé : le reçu ne change plus jamais.
            'snapshot' => [
                'company_name' => config('receipts.company_name'),
                'support_email' => config('receipts.support_email'),
                'support_phone' => config('receipts.support_phone'),
                'receipt_number' => $number,
                'payment_reference' => $payment->reference,
                'order_reference' => $order->reference,
                'supplier_name' => $supplier->business_name,
                'description' => $order->description,
                'amount_minor' => (int) $payment->amount_minor,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'status' => 'paid',
                'created_at' => $payment->created_at->toIso8601String(),
                'paid_at' => $issuedAt->toIso8601String(),
            ],
            'issued_at' => $issuedAt,
        ]);
    }

        public static function issueRefundFor(Payment $payment, Refund $refund): Receipt
    {
        return Receipt::firstOrCreate(
            ['payment_id' => $payment->id, 'receipt_type' => 'refund'],
            [
                'receipt_number' => 'RC-'.now()->format('Y').sprintf('-R%08d', $payment->id),
                'user_id' => $payment->user_id,
                'status' => 'refunded',
                'snapshot' => [
                    'payment_reference' => $payment->reference,
                    'refund_reference' => $refund->reference,
                    'amount_minor' => $refund->amount_minor,
                    'currency' => $refund->currency,
                    'reason' => $refund->reason,
                    'issued_at' => now()->toIso8601String(),
                ],
                'issued_at' => now(),
            ]
        );
    }
}

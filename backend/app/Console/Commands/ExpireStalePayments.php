<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TransactionHistory;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireStalePayments extends Command
{
    protected $signature = 'payments:expire-stale';

    protected $description = "Fait expirer les paiements created/pending/requires_action trop anciens et libère la commande pour une nouvelle tentative.";

    public function handle(): int
    {
        $minutes = (int) config('payments.expiration_minutes', 30);
        $threshold = now()->subMinutes($minutes);

        $staleIds = Payment::whereIn('status', ['created', 'pending', 'requires_action'])
            ->where('created_at', '<', $threshold)
            ->pluck('id');

        $count = 0;

        foreach ($staleIds as $id) {
            DB::transaction(function () use ($id, &$count) {
                $payment = Payment::whereKey($id)->lockForUpdate()->first();

                if ($payment === null || ! in_array($payment->status, ['created', 'pending', 'requires_action'], true)) {
                    return;
                }

                $previousStatus = $payment->status;
                $payment->update(['status' => PaymentStatus::Expired->value]);

                Order::whereKey($payment->order_id)->update(['status' => 'created']);

                TransactionHistory::create([
                    'payment_id' => $payment->id,
                    'previous_status' => $previousStatus,
                    'new_status' => PaymentStatus::Expired->value,
                    'event_type' => 'payment.expired_auto',
                    'event_reference' => null,
                    'actor_type' => 'system',
                    'actor_id' => null,
                ]);

                NotificationDispatcher::dispatch($payment->user_id, $payment->id, 'payment.expired');

                $count++;
            });
        }

        $this->info("{$count} paiement(s) expiré(s).");

        return self::SUCCESS;
    }
}

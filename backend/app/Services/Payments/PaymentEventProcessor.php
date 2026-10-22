<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\TransactionHistory;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PaymentEventProcessor
{
    private const EVENT_TO_STATUS = [
        'payment.succeeded' => PaymentStatus::Paid,
        'payment.failed' => PaymentStatus::Failed,
        'payment.requires_action' => PaymentStatus::RequiresAction,
        'payment.expired' => PaymentStatus::Expired,
    ];

    // Seuls ces statuts peuvent encore évoluer ; paid, failed et expired sont terminaux.
    private const TRANSITIONS = [
        'pending' => ['requires_action', 'paid', 'failed', 'expired'],
        'requires_action' => ['pending', 'paid', 'failed', 'expired'],
    ];

    /**
     * Retourne : processed, ignored, anomaly, amount_mismatch ou duplicate.
     *
     * @param  array{id: string, type: string, data: array<string, mixed>}  $event
     */
    public function process(string $provider, array $event): string
    {
        if (PaymentEvent::where('provider', $provider)->where('event_id', $event['id'])->exists()) {
            return 'duplicate';
        }

        try {
            return DB::transaction(fn () => $this->handle($provider, $event));
        } catch (UniqueConstraintViolationException) {
            // Même événement reçu en parallèle : l'autre requête l'a déjà enregistré.
            return 'duplicate';
        }
    }

    private function handle(string $provider, array $event): string
    {
        $payment = Payment::where('provider', $provider)
            ->where('provider_reference', $event['data']['provider_reference'])
            ->lockForUpdate()
            ->first();

        // Le prestataire peut notifier avant que notre référence soit enregistrée : 404, il réessaiera.
        abort_if($payment === null, 404, 'Paiement inconnu.');

        $target = self::EVENT_TO_STATUS[$event['type']] ?? null;
        $current = PaymentStatus::from($payment->status);

        [$outcome, $apply] = $this->decide($payment, $current, $target, $event);

        PaymentEvent::create([
            'provider' => $provider,
            'event_id' => $event['id'],
            'event_type' => $event['type'],
            'payment_id' => $payment->id,
            'status' => $outcome,
            'payload' => $event,
            'processed_at' => now(),
        ]);

        if ($apply) {
            $this->apply($payment, $current, $target, $event);
        }

        if (in_array($outcome, ['anomaly', 'amount_mismatch'], true)) {
            Log::error('payments.webhook.'.$outcome, [
                'provider' => $provider,
                'payment_reference' => $payment->reference,
                'event_id' => $event['id'],
                'current_status' => $current->value,
            ]);
        }

        return $outcome;
    }

    /**
     * @return array{0: string, 1: bool} [résultat, appliquer le changement]
     */
    private function decide(Payment $payment, PaymentStatus $current, ?PaymentStatus $target, array $event): array
    {
        if ($target === null || $target === $current) {
            return ['ignored', false];
        }

        $allowed = in_array($target->value, self::TRANSITIONS[$current->value] ?? [], true);

        if ($target === PaymentStatus::Paid) {
            // Succès reçu pour un paiement déjà échoué ou expiré : à vérifier manuellement.
            if (! $allowed) {
                return ['anomaly', false];
            }

            $amount = $event['data']['amount_minor'] ?? null;
            $currency = $event['data']['currency'] ?? null;

            if ($amount === null || (int) $amount !== (int) $payment->amount_minor || $currency !== $payment->currency) {
                return ['amount_mismatch', false];
            }

            return ['processed', true];
        }

        return $allowed ? ['processed', true] : ['ignored', false];
    }

    private function apply(Payment $payment, PaymentStatus $current, PaymentStatus $target, array $event): void
    {
        $payment->update([
            'status' => $target->value,
            'confirmed_at' => $target === PaymentStatus::Paid ? now() : $payment->confirmed_at,
        ]);

        Order::whereKey($payment->order_id)->update([
            'status' => $this->orderStatusFor($target)->value,
        ]);

        TransactionHistory::create([
            'payment_id' => $payment->id,
            'previous_status' => $current->value,
            'new_status' => $target->value,
            'event_type' => $event['type'],
            'event_reference' => $event['id'],
            'actor_type' => 'provider',
            'actor_id' => null,
        ]);
        
        // Un paiement confirmé donne lieu à son reçu, dans la même transaction que le changement de statut.
        if ($target === PaymentStatus::Paid) {
            ReceiptIssuer::issueFor($payment);
        }
    }

    private function orderStatusFor(PaymentStatus $status): OrderStatus
    {
        return match ($status) {
            PaymentStatus::Paid => OrderStatus::Paid,
            // Après un échec ou une expiration, la commande peut être payée à nouveau.
            PaymentStatus::Failed, PaymentStatus::Expired => OrderStatus::Created,
            default => OrderStatus::Pending,
        };
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentIntentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Allocation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\TransactionHistory;
use App\Services\Commission\CommissionCalculator;
use App\Services\Commission\NoCommissionRuleException;
use App\Services\Payments\PaymentGateway;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function intent(
        PaymentIntentRequest $request,
        Order $order,
        PaymentGateway $gateway,
        CommissionCalculator $calculator,
    ): JsonResponse {
        $user = $request->user();

        abort_unless((int) $order->user_id === (int) $user->id, 404);

        $key = $request->validated('idempotency_key');
        $method = $request->validated('payment_method');

        // Phase 1 : décisions et écritures en base, dans une seule transaction.
        try {
            [$payment, $replayed] = DB::transaction(function () use ($order, $user, $key, $method, $gateway, $calculator) {
                // Verrou sur la commande : deux demandes simultanées sont traitées l'une après l'autre.
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                $existing = Payment::where('user_id', $user->id)->where('idempotency_key', $key)->first();

                if ($existing) {
                    abort_unless(
                        (int) $existing->order_id === (int) $locked->id && $existing->payment_method === $method,
                        409,
                        "Cette clé d'idempotence a déjà servi pour une autre demande."
                    );

                    return [$existing, true];
                }

                abort_if(
                    Payment::where('order_id', $locked->id)->whereIn('status', PaymentStatus::blockingValues())->exists(),
                    409,
                    'Un paiement est déjà en cours ou confirmé pour cette commande.'
                );

                $supplier = Supplier::findOrFail($locked->supplier_id);
                abort_unless($supplier->status === 'active', 422, "Ce fournisseur n'accepte plus de paiements.");

                $split = $calculator->forSupplier($locked->amount_minor, $locked->currency, $supplier);

                $payment = Payment::create([
                    'reference' => 'PAY-'.strtoupper((string) Str::ulid()),
                    'order_id' => $locked->id,
                    'user_id' => $user->id,
                    'provider' => $gateway->name(),
                    'amount_minor' => $locked->amount_minor,
                    'currency' => $locked->currency,
                    'payment_method' => $method,
                    'status' => PaymentStatus::Created->value,
                    'idempotency_key' => $key,
                ]);

                // Répartition figée avec la règle de commission utilisée à cet instant.
                Allocation::create([
                    'payment_id' => $payment->id,
                    'beneficiary_type' => 'company',
                    'beneficiary_id' => null,
                    'gross_amount_minor' => $split->commissionMinor,
                    'fees_minor' => 0,
                    'net_amount_minor' => $split->commissionMinor,
                    'currency' => $locked->currency,
                    'status' => 'pending',
                    'commission_rule_id' => $split->ruleId,
                    'commission_rule_version' => $split->ruleVersion,
                ]);

                Allocation::create([
                    'payment_id' => $payment->id,
                    'beneficiary_type' => 'supplier',
                    'beneficiary_id' => $supplier->id,
                    'gross_amount_minor' => $split->supplierShareMinor,
                    'fees_minor' => 0,
                    'net_amount_minor' => $split->supplierShareMinor,
                    'currency' => $locked->currency,
                    'status' => 'pending',
                    'commission_rule_id' => $split->ruleId,
                    'commission_rule_version' => $split->ruleVersion,
                ]);

                return [$payment, false];
            });
        } catch (NoCommissionRuleException) {
            return response()->json([
                'message' => "Aucune règle de commission n'est configurée pour ce paiement.",
            ], 422);
        } catch (UniqueConstraintViolationException) {
            // Même clé reçue en parallèle : on renvoie le paiement déjà créé.
            $payment = Payment::where('user_id', $user->id)->where('idempotency_key', $key)->firstOrFail();

            return $this->respond($payment, 200, true);
        }

        if ($replayed) {
            return $this->respond($payment, 200, true);
        }

        // Phase 2 : appel du prestataire, hors transaction.
        try {
            $result = $gateway->initiate($payment);

            $payment->update([
                'provider_reference' => $result->providerReference,
                'status' => $result->status->value,
            ]);
            $order->update(['status' => OrderStatus::Pending->value]);

            TransactionHistory::create([
                'payment_id' => $payment->id,
                'previous_status' => PaymentStatus::Created->value,
                'new_status' => $result->status->value,
                'event_type' => 'payment.initiated',
                'event_reference' => $result->providerReference,
                'actor_type' => 'client',
                'actor_id' => $user->id,
            ]);
        } catch (Throwable $e) {
            report($e);

            $payment->update(['status' => PaymentStatus::Failed->value]);

            TransactionHistory::create([
                'payment_id' => $payment->id,
                'previous_status' => PaymentStatus::Created->value,
                'new_status' => PaymentStatus::Failed->value,
                'event_type' => 'payment.initiation_failed',
                'event_reference' => null,
                'actor_type' => 'client',
                'actor_id' => $user->id,
            ]);

            return $this->respond(
                $payment,
                502,
                false,
                'Le prestataire de paiement est indisponible. Réessayez avec une nouvelle clé.'
            );
        }

        return $this->respond($payment, 201);
    }

    public function show(Request $request, Payment $payment): PaymentResource
    {
        abort_unless((int) $payment->user_id === (int) $request->user()->id, 404);

        return new PaymentResource($payment);
    }

    private function respond(Payment $payment, int $status, bool $replayed = false, ?string $message = null): JsonResponse
    {
        $response = (new PaymentResource($payment))
            ->additional($message ? ['message' => $message] : [])
            ->response()
            ->setStatusCode($status);

        return $replayed ? $response->header('Idempotent-Replayed', 'true') : $response;
    }
}

<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Passerelle simulée, utilisée tant que le prestataire de paiement n'est pas choisi.
 */
final class SandboxGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function initiate(Payment $payment): GatewayResult
    {
        return new GatewayResult(
            providerReference: 'sbx_'.strtolower((string) Str::ulid()),
            status: PaymentStatus::Pending,
        );
    }
}

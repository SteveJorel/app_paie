<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    public function name(): string;

    /**
     * Démarre le paiement chez le prestataire. Lance une exception en cas d'échec.
     */
    public function initiate(Payment $payment): GatewayResult;
}

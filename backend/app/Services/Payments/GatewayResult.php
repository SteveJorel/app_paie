<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;

final class GatewayResult
{
    public function __construct(
        public readonly string $providerReference,
        public readonly PaymentStatus $status,
        public readonly ?string $actionUrl = null,
    ) {
    }
}

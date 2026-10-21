<?php

namespace App\Services\Commission;

final class CommissionBreakdown
{
    public function __construct(
        public readonly int $amountMinor,
        public readonly string $currency,
        public readonly int $commissionMinor,
        public readonly int $supplierShareMinor,
        public readonly ?int $ruleId,
        public readonly ?int $ruleVersion,
    ) {
    }
}

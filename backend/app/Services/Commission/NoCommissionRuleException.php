<?php

namespace App\Services\Commission;

use RuntimeException;

final class NoCommissionRuleException extends RuntimeException
{
    public static function forScope(string $currency, ?int $supplierId): self
    {
        return new self(
            "Aucune règle de commission applicable (devise {$currency}, fournisseur ".($supplierId ?? 'par défaut').').'
        );
    }
}

<?php

namespace App\Services\Commission;

use App\Models\CommissionRule;
use App\Models\Supplier;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class CommissionCalculator
{
    // 10^14 unités mineures : garantit que montant x points de base reste un entier 64 bits.
    public const MAX_AMOUNT_MINOR = 100_000_000_000_000;

    /**
     * Calcul pur, en entiers : aucun nombre flottant.
     */
    public function calculate(int $amountMinor, CommissionRule $rule, string $currency = ''): CommissionBreakdown
    {
        if ($amountMinor <= 0 || $amountMinor > self::MAX_AMOUNT_MINOR) {
            throw new InvalidArgumentException('Montant invalide.');
        }

        // Arrondi à l'unité mineure la plus proche, 0,5 arrondi vers le haut.
        $percentPart = intdiv($amountMinor * $rule->percentage_bps + 5000, 10000);

        $commission = match ($rule->type) {
            CommissionRule::TYPE_PERCENTAGE => $percentPart,
            CommissionRule::TYPE_FIXED => $rule->fixed_minor,
            CommissionRule::TYPE_COMBINED => $rule->fixed_minor + $percentPart,
            default => throw new InvalidArgumentException('Type de commission inconnu.'),
        };

        // La commission ne peut jamais dépasser le montant payé.
        $commission = min($commission, $amountMinor);

        return new CommissionBreakdown(
            amountMinor: $amountMinor,
            currency: $currency !== '' ? $currency : (string) $rule->currency,
            commissionMinor: $commission,
            supplierShareMinor: $amountMinor - $commission,
            ruleId: $rule->id,
            ruleVersion: $rule->version,
        );
    }

    /**
     * Choisit la règle applicable puis calcule la répartition.
     */
    public function forSupplier(
        int $amountMinor,
        string $currency,
        ?Supplier $supplier = null,
        ?CarbonInterface $at = null,
    ): CommissionBreakdown {
        $rule = $this->resolveRule($currency, $supplier?->id, $at ?? now());

        return $this->calculate($amountMinor, $rule, $currency);
    }

    private function resolveRule(string $currency, ?int $supplierId, CarbonInterface $at): CommissionRule
    {
        $rule = CommissionRule::query()
            ->where('status', CommissionRule::STATUS_ACTIVE)
            ->where('currency', $currency)
            ->where('valid_from', '<=', $at)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>', $at))
            ->where(function ($q) use ($supplierId) {
                $q->whereNull('supplier_id');
                if ($supplierId !== null) {
                    $q->orWhere('supplier_id', $supplierId);
                }
            })
            ->orderByRaw('supplier_id IS NULL')
            ->orderByDesc('valid_from')
            ->orderByDesc('version')
            ->first();

        if (! $rule) {
            throw NoCommissionRuleException::forScope($currency, $supplierId);
        }

        return $rule;
    }
}

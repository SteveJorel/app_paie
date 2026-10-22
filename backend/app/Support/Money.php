<?php

namespace App\Support;

final class Money
{
    private const DECIMALS = ['XAF' => 0, 'NGN' => 2];

    /**
     * Affiche un montant en unités mineures, sans nombre flottant.
     * Exemples : 10000 XAF => "10 000 XAF" ; 1000000 NGN (kobo) => "10 000,00 NGN".
     */
    public static function format(int $minor, string $currency): string
    {
        $decimals = self::DECIMALS[$currency] ?? 2;
        $factor = 10 ** $decimals;

        $text = number_format(intdiv($minor, $factor), 0, '', ' ');

        if ($decimals > 0) {
            $text .= ','.str_pad((string) ($minor % $factor), $decimals, '0', STR_PAD_LEFT);
        }

        return $text.' '.$currency;
    }
}

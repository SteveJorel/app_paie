<?php

namespace App\Services\Payments;

final class WebhookSignature
{
    public static function sign(string $secret, string $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    public static function verify(
        string $secret,
        string $timestamp,
        string $body,
        string $signature,
        int $toleranceSeconds = 300,
    ): bool {
        if (! ctype_digit($timestamp) || abs(now()->getTimestamp() - (int) $timestamp) > $toleranceSeconds) {
            return false;
        }

        // Comparaison à temps constant.
        return hash_equals(self::sign($secret, $timestamp, $body), $signature);
    }
}

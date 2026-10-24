<?php

return [
    'providers' => [
        'sandbox' => [
            'webhook_secret' => env('SANDBOX_WEBHOOK_SECRET'),
            'webhook_tolerance' => 300,
        ],
    ],

    // Durée (en minutes) au-delà de laquelle un paiement non confirmé est expiré automatiquement.
    'expiration_minutes' => env('PAYMENT_EXPIRATION_MINUTES', 30),
];

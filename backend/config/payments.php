<?php

return [
    'providers' => [
        'sandbox' => [
            'webhook_secret' => env('SANDBOX_WEBHOOK_SECRET'),
            // Écart maximal (en secondes) accepté entre l'horodatage signé et l'heure du serveur.
            'webhook_tolerance' => 300,
        ],
    ],
];

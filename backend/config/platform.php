<?php

// Lus via config() et non env() : env() renvoie null une fois `php artisan config:cache` exécuté.
return [
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),
];

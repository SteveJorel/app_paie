<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libère les paiements créés/en attente trop anciens (section 18.2 du cahier des charges).
Schedule::command('payments:expire-stale')->everyFiveMinutes();

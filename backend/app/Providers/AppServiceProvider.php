<?php

namespace App\Providers;

use App\Services\Payments\PaymentGateway;
use App\Services\Payments\SandboxGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Passerelle simulée tant que le prestataire de paiement n'est pas choisi.
        $this->app->bind(PaymentGateway::class, SandboxGateway::class);
    }

    public function boot(): void
    {
        //
    }
}

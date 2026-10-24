<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\CountryPaymentMethod;
use App\Models\Currency;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PlatformConfigSeeder extends Seeder
{
    public function run(): void
    {
        Country::updateOrCreate(['code' => 'CM'], ['name' => 'Cameroun', 'status' => 'active']);
        Country::updateOrCreate(['code' => 'NG'], ['name' => 'Nigeria', 'status' => 'active']);

        Currency::updateOrCreate(['code' => 'XAF'], ['name' => 'Franc CFA (BEAC)', 'minor_unit_factor' => 1, 'status' => 'active']);
        Currency::updateOrCreate(['code' => 'NGN'], ['name' => 'Naira', 'minor_unit_factor' => 100, 'status' => 'active']);

        PaymentMethod::updateOrCreate(['code' => 'mobile_money'], ['name' => 'Mobile Money', 'status' => 'active']);
        PaymentMethod::updateOrCreate(['code' => 'card'], ['name' => 'Carte bancaire', 'status' => 'active']);
        PaymentMethod::updateOrCreate(['code' => 'bank_transfer'], ['name' => 'Virement bancaire', 'status' => 'active']);

        // Aucun moyen n'est encore confirmé disponible tant que le prestataire n'est pas choisi (section 18 du cahier des charges).
        foreach (['CM', 'NG'] as $country) {
            foreach (['mobile_money', 'card', 'bank_transfer'] as $method) {
                CountryPaymentMethod::updateOrCreate(
                    ['country_code' => $country, 'payment_method_code' => $method],
                    ['status' => 'pending']
                );
            }
        }
    }
}

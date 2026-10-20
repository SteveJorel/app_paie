<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_name' => fake()->company(),
            'country_code' => 'CM',
            'category' => 'restauration',
            'settlement_currency' => 'XAF',
            'status' => 'active',
        ];
    }
}
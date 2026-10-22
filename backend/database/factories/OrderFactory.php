<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => 'ORD-'.strtoupper((string) Str::ulid()),
            'user_id' => User::factory(),
            'supplier_id' => Supplier::factory(),
            'description' => 'Facture de test',
            'amount_minor' => 10000,
            'currency' => 'XAF',
            'status' => 'created',
        ];
    }
}

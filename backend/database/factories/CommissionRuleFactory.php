<?php

namespace Database\Factories;

use App\Models\CommissionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionRule>
 */
class CommissionRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => null,
            'currency' => 'XAF',
            'type' => 'percentage',
            'percentage_bps' => 500,
            'fixed_minor' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
            'version' => 1,
            'status' => 'active',
        ];
    }
}

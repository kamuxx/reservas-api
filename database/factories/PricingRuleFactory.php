<?php

namespace Database\Factories;

use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PricingRuleFactory extends Factory
{
    protected $model = PricingRule::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'adjustment_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'price_adjustment' => $this->faker->randomFloat(2, 5, 50),
        ];
    }
}

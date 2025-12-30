<?php

namespace Database\Factories;

use App\Models\PricingRule;
use App\Models\SpaceType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Space>
 */
class SpaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        User::factory()->create();
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->sentence(),
            'capacity' => $this->faker->numberBetween(1, 100),
            'spaces_type_id' => SpaceType::inRandomOrder()->first()->uuid,
            'status_id' => Status::inRandomOrder()->first()->uuid,
            'pricing_rule_id' => PricingRule::inRandomOrder()->first()->uuid,
            'is_active' => $this->faker->boolean(),
            'created_by' => User::inRandomOrder()->first()->uuid,
        ];
    }
}

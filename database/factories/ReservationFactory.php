<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Space;
use App\Models\Status;
use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'reserved_by' => User::factory(),
            'space_id' => Space::factory(),
            'status_id' => Status::where('name', 'active')->first()?->uuid ?? Status::factory(),
            'event_name' => $this->faker->sentence(3),
            'event_description' => $this->faker->paragraph,
            'event_date' => $this->faker->date(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'event_price' => $this->faker->randomFloat(2, 50, 500),
            'pricing_rule_id' => PricingRule::factory(),
        ];
    }
}

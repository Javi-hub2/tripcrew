<?php

namespace Database\Factories;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripDayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'date' => fake()->unique()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
        ];
    }
}

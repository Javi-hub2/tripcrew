<?php

namespace Database\Factories;

use App\Models\TripDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trip_day_id' => TripDay::factory(),
            'name' => fake()->randomElement(['Kajakken', 'Fietstour', 'Kookworkshop', 'Museumbezoek', 'Surfles']),
            'capacity' => fake()->numberBetween(4, 15),
            'deadline' => now()->addDays(7),
        ];
    }

    public function deadlinePassed(): static
    {
        return $this->state(['deadline' => now()->subDay()]);
    }
}

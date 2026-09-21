<?php

namespace Database\Factories;

use App\Models\TripDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trip_day_id' => TripDay::factory(),
            'time' => fake()->randomElement(['08:30', '10:00', '13:00', '15:30', '19:00']),
            'title' => fake()->randomElement(['Ontbijt', 'Stadswandeling', 'Lunch', 'Vrije tijd', 'Diner met de groep']),
            'location' => fake()->randomElement(['Hostel', 'Centrum', 'Strand', 'Oude stad']),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');

        return [
            'name' => fake()->randomElement(['Barcelona', 'Lissabon', 'Praag', 'Berlijn']).' '.fake()->year('+1 year'),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify('+4 days')->format('Y-m-d'),
        ];
    }
}

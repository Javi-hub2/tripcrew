<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trip_id' => Trip::factory(),
            'label' => fake()->randomElement(['Paspoort gecontroleerd', 'Reisverzekering geregeld', 'Tas ingepakt']),
            'checked' => false,
        ];
    }
}

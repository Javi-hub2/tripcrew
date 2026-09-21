<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Alleen fictieve testdata. */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake('nl_NL')->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'reiziger',
            'password' => static::$password ??= Hash::make('password'),
            'activated_at' => now(),
            'activation_token' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function coordinator(): static
    {
        return $this->state(['role' => 'coordinator']);
    }

    /** Uitgenodigde reiziger die zijn account nog moet activeren (FE-01). */
    public function notActivated(): static
    {
        return $this->state([
            'password' => Hash::make(Str::random(40)), // onbekend, onbruikbaar wachtwoord
            'activated_at' => null,
            'activation_token' => Str::random(64),
        ]);
    }
}

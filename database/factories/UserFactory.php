<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),

            'email' => fake()->unique()->safeEmail(),

            'password_hash' => Hash::make('password123'),

            'rol_id' => 2,

            'estado' => true,
        ];
    }
}

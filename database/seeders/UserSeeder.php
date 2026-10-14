<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'nombre' => 'Administrador AgileVet',
            'email' => 'admin@agilevet.com',
            'rol_id' => 1,
            'estado' => true,
        ]);

        User::factory()->create([
            'nombre' => 'Dr. Juan Veterinario',
            'email' => 'veterinario@agilevet.com',
            'rol_id' => 2,
            'estado' => true,
        ]);

        User::factory()->create([
            'nombre' => 'Recepcion AgileVet',
            'email' => 'recepcion@agilevet.com',
            'rol_id' => 3,
            'estado' => true,
        ]);
    }
}
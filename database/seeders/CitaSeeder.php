<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Mascota;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class CitaSeeder extends Seeder
{
    public function run(): void
    {
        $hayMascotas = Mascota::query()->exists();

        $hayVeterinarios = User::query()
            ->whereHas('role', function ($query) {
                $query->where('nombre', 'Veterinario');
            })
            ->where('estado', true)
            ->exists();

        if (!$hayMascotas || !$hayVeterinarios) {
            throw new RuntimeException(
                'Primero debes registrar mascotas y veterinarios activos.'
            );
        }

        // Crear 5 citas pasadas
        Cita::factory()
            ->count(5)
            ->pasada()
            ->create();

        // Crear 5 citas futuras
        Cita::factory()
            ->count(5)
            ->futura()
            ->create();
    }
}


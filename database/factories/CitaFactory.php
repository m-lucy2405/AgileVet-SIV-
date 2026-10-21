<?php

namespace Database\Factories;

use App\Models\Cita;
use App\Models\Mascota;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<Cita>
 */
class CitaFactory extends Factory
{
    protected $model = Cita::class;

    public function definition(): array
    {
        $mascotaId = Mascota::query()
            ->inRandomOrder()
            ->value('id');

        $veterinarioId = User::query()
            ->whereHas('role', function ($query) {
                $query->where('nombre', 'Veterinario');
            })
            ->where('estado', true)
            ->inRandomOrder()
            ->value('id');

        if (! $mascotaId || ! $veterinarioId) {
            throw new RuntimeException(
                'Se necesita una mascota y un veterinario activo.'
            );
        }

        return [
            'mascota_id' => $mascotaId,
            'veterinario_id' => $veterinarioId,

            'fecha_cita' => fake()->dateTimeBetween(
                '-1 month',
                '+1 month'
            ),

            'estado' => 'pendiente',

            'motivo' => fake()->randomElement([
                'Consulta general',
                'Vacunación',
                'Desparasitación',
                'Control médico',
                'Revisión de salud',
            ]),

            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Generar citas anteriores.
     */
    public function pasada(): static
    {
        return $this->state(fn () => [
            'fecha_cita' => fake()->dateTimeBetween(
                '-3 months',
                '-1 day'
            ),
            'estado' => 'atendida',
        ]);
    }

    /**
     * Generar citas futuras.
     */
    public function futura(): static
    {
        return $this->state(fn () => [
            'fecha_cita' => fake()->dateTimeBetween(
                '+1 day',
                '+3 months'
            ),
            'estado' => 'pendiente',
        ]);
    }
}

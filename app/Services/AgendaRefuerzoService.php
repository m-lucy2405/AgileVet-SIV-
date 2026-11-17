<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Mascota;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Agenda automáticamente la cita de refuerzo de una vacuna o desparasitación.
 *
 * Reglas (las mismas de las citas normales):
 * - Lunes a sábado, de 07:00 a 17:30, en bloques de 30 minutos, siempre en el futuro.
 * - Sin choques con otras citas activas del veterinario ni de la mascota.
 * - Si la fecha cae domingo, se adelanta al sábado.
 * - Si el día está lleno, se prueba el día hábil anterior (hasta 7 días hábiles antes, sin llegar a hoy).
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class AgendaRefuerzoService
{
    public const DURACION_MINUTOS = 30;

    public const PRIMERA_HORA = '07:00';

    public const ULTIMA_HORA = '17:30';

    /**
     * Días hábiles que se intenta adelantar como máximo si no hay horario libre.
     */
    public const DIAS_MAXIMOS_ADELANTO = 7;

    /**
     * Calcula la fecha ideal del refuerzo: fecha de aplicación + intervalo del catálogo.
     */
    public function fechaRefuerzo(CarbonImmutable $fechaAplicacion, int $intervaloDias): CarbonImmutable
    {
        return $fechaAplicacion->startOfDay()->addDays($intervaloDias);
    }

    /**
     * Crea la cita de refuerzo en la primera hora libre, adelantando el día si hace falta.
     *
     * @param  Mascota  $mascota  Mascota que recibirá el refuerzo.
     * @param  User  $veterinario  Veterinario que atenderá la cita.
     * @param  CarbonImmutable  $fechaObjetivo  Día ideal del refuerzo.
     * @param  string  $motivo  Motivo de la cita (por ejemplo, "Refuerzo de Antirrábica").
     * @return Cita|null La cita creada, o null si no se encontró ningún horario libre.
     */
    public function agendar(Mascota $mascota, User $veterinario, CarbonImmutable $fechaObjetivo, string $motivo): ?Cita
    {
        return DB::transaction(function () use ($mascota, $veterinario, $fechaObjetivo, $motivo): ?Cita {
            // Mismo orden de bloqueo que CitaController (veterinario y luego mascota),
            // para que una cita normal y un refuerzo simultáneos no tomen la misma hora
            User::query()->whereKey($veterinario->getKey())->lockForUpdate()->firstOrFail();
            Mascota::query()->whereKey($mascota->getKey())->lockForUpdate()->firstOrFail();

            $manana = CarbonImmutable::today()->addDay();
            $dia = $fechaObjetivo->startOfDay();
            $intentos = 0;

            while ($intentos <= self::DIAS_MAXIMOS_ADELANTO && $dia->gte($manana)) {
                // El domingo no se atiende: se adelanta al sábado
                if ($dia->isSunday()) {
                    $dia = $dia->subDay();

                    continue;
                }

                $hora = $this->primeraHoraLibre($dia, $veterinario->id, $mascota->id);

                if ($hora !== null) {
                    return Cita::query()->create([
                        'mascota_id' => $mascota->id,
                        'veterinario_id' => $veterinario->id,
                        'fecha_cita' => $hora->format('Y-m-d H:i:s'),
                        'estado' => 'pendiente',
                        'motivo' => $motivo,
                        'observaciones' => 'Cita agendada automáticamente al registrar la aplicación.',
                    ]);
                }

                $dia = $dia->subDay();
                $intentos++;
            }

            return null;
        });
    }

    /**
     * Describe la fecha de la cita en español, por ejemplo: "el sábado 05/12/2026 a las 07:00".
     */
    public function describir(Cita $cita): string
    {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

        return sprintf(
            'el %s %s a las %s',
            $dias[$cita->fecha_cita->dayOfWeek],
            $cita->fecha_cita->format('d/m/Y'),
            $cita->fecha_cita->format('H:i'),
        );
    }

    /**
     * Primera hora del día sin choques para el veterinario ni para la mascota.
     */
    private function primeraHoraLibre(CarbonImmutable $dia, int $veterinarioId, int $mascotaId): ?CarbonImmutable
    {
        $hora = $dia->setTimeFromTimeString(self::PRIMERA_HORA);
        $ultima = $dia->setTimeFromTimeString(self::ULTIMA_HORA);

        while ($hora->lte($ultima)) {
            if ($hora->isFuture()
                && ! $this->estaOcupado('veterinario_id', $veterinarioId, $hora)
                && ! $this->estaOcupado('mascota_id', $mascotaId, $hora)) {
                return $hora;
            }

            $hora = $hora->addMinutes(self::DURACION_MINUTOS);
        }

        return null;
    }

    /**
     * Indica si ya hay una cita activa que se cruce con ese bloque de 30 minutos.
     */
    private function estaOcupado(string $columna, int $id, CarbonImmutable $hora): bool
    {
        return Cita::query()
            ->where($columna, $id)
            ->where('estado', '!=', 'cancelada')
            ->overlappingInterval($hora, $hora->addMinutes(self::DURACION_MINUTOS), self::DURACION_MINUTOS)
            ->exists();
    }
}
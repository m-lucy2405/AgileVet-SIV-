<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Mascota;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Administra la agenda y el ciclo de vida de las citas veterinarias.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class CitaController extends Controller
{
    /**
     * Estados admitidos por el flujo de citas.
     *
     * @var array<int, string>
     */
    private const ESTADOS = [
        'pendiente',
        'atendida',
        'cancelada',
        'no_asistio',
    ];

    /**
     * Duración fija de los turnos configurada en el calendario.
     */
    private const DURACION_CITA_MINUTOS = 30;

    /**
     * Muestra el calendario junto con las mascotas y los veterinarios disponibles.
     *
     * @return View Vista con los datos requeridos por el calendario de citas.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function index(): View
    {
        $citas = Cita::query()
            ->with(['mascota.cliente', 'veterinario'])
            ->orderBy('fecha_cita')
            ->get()
            ->map(fn (Cita $cita): array => [
                'id' => $cita->id,
                'veterinario_id' => $cita->veterinario_id,
                'start' => $cita->fecha_cita->toIso8601String(),
                'end' => $cita->fecha_cita
                    ->addMinutes(self::DURACION_CITA_MINUTOS)
                    ->toIso8601String(),
                'estado' => $cita->estado,
                'mascota' => $cita->mascota->nombre,
                'motivo' => $cita->motivo,
                'cliente' => $cita->mascota->cliente->nombre,
                'telefono' => $cita->mascota->cliente->telefono,
                'veterinario' => $cita->veterinario->nombre,
            ]);

        $mascotas = Mascota::query()
            ->with('cliente')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Mascota $mascota): array => [
                'id' => $mascota->id,
                'nombre' => $mascota->nombre,
                'cliente' => $mascota->cliente->nombre,
                'telefono' => $mascota->cliente->telefono,
            ]);

        $veterinarios = User::query()
            ->where('estado', true)
            ->whereHas(
                'role',
                fn (EloquentBuilder $query): EloquentBuilder => $query->where(
                    'nombre',
                    'Veterinario',
                ),
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (User $veterinario): array => [
                'id' => $veterinario->id,
                'nombre' => $veterinario->nombre,
            ]);

        return view('citas.index', [
            'citas' => $citas,
            'mascotas' => $mascotas,
            'veterinarios' => $veterinarios,
        ]);
    }

    /**
     * Valida y registra una cita si el veterinario tiene disponible el horario solicitado.
     *
     * @param  Request  $request  Petición con mascota, veterinario, fecha y detalle de la cita.
     * @return RedirectResponse Redirección al calendario con el resultado de la operación.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mascota_id' => ['required', 'integer', Rule::exists('mascotas', 'id')],
            'veterinario_id' => ['required', 'integer', $this->veterinarianIdRule()],
            'fecha_cita' => ['required', 'date_format:Y-m-d\TH:i'],
            'motivo' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $appointmentStart = CarbonImmutable::parse(
            $validated['fecha_cita'],
            (string) config('app.timezone'),
        );
        $this->assertAppointmentIsWithinBusinessHours($appointmentStart);

        DB::transaction(function () use ($validated, $appointmentStart): void {
            $veterinarianId = (int) $validated['veterinario_id'];
            $this->lockVeterinarian($veterinarianId);
            $this->assertNoVeterinarianConflict($appointmentStart, $veterinarianId);

            Cita::query()->create([
                'mascota_id' => $validated['mascota_id'],
                'veterinario_id' => $veterinarianId,
                'fecha_cita' => $appointmentStart->format('Y-m-d H:i:s'),
                'estado' => 'pendiente',
                'motivo' => $validated['motivo'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]);
        });

        return redirect()
            ->route('citas.index')
            ->with('status', 'La cita se registró correctamente.');
    }

    /**
     * Actualiza los datos de una cita y valida su nuevo horario dentro de una transacción.
     *
     * @param  Request  $request  Petición con los nuevos datos de la cita.
     * @param  Cita  $cita  Cita resuelta mediante route model binding.
     * @return RedirectResponse Redirección al calendario con el resultado de la operación.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function update(Request $request, Cita $cita): RedirectResponse
    {
        $validated = $request->validate([
            'mascota_id' => ['required', 'integer', Rule::exists('mascotas', 'id')],
            'veterinario_id' => ['required', 'integer', $this->veterinarianIdRule()],
            'fecha_cita' => ['required', 'date_format:Y-m-d\TH:i'],
            'motivo' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $appointmentStart = CarbonImmutable::parse(
            $validated['fecha_cita'],
            (string) config('app.timezone'),
        );
        $this->assertAppointmentIsWithinBusinessHours($appointmentStart);

        DB::transaction(function () use ($cita, $validated, $appointmentStart): void {
            $lockedCita = Cita::query()
                ->whereKey($cita->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $veterinarianId = (int) $validated['veterinario_id'];

            $this->lockVeterinarian($veterinarianId);

            if ($lockedCita->estado !== 'cancelada') {
                $this->assertNoVeterinarianConflict(
                    $appointmentStart,
                    $veterinarianId,
                    (int) $lockedCita->getKey(),
                );
            }

            $lockedCita->update([
                'mascota_id' => $validated['mascota_id'],
                'veterinario_id' => $veterinarianId,
                'fecha_cita' => $appointmentStart->format('Y-m-d H:i:s'),
                'motivo' => $validated['motivo'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]);
        });

        return redirect()
            ->route('citas.index')
            ->with('status', 'La cita se actualizó correctamente.');
    }

    /**
     * Cancela una cita sin eliminar su registro ni su historial.
     *
     * @param  Cita  $cita  Cita resuelta mediante route model binding.
     * @return RedirectResponse Redirección al calendario con el resultado de la operación.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function destroy(Cita $cita): RedirectResponse
    {
        $this->updateAppointmentStatus($cita, 'cancelada');

        return redirect()
            ->route('citas.index')
            ->with('status', 'La cita se canceló correctamente.');
    }

    /**
     * Cambia el estado de una cita a uno de los estados admitidos por el sistema.
     *
     * @param  Request  $request  Petición con el nuevo estado de la cita.
     * @param  Cita  $cita  Cita resuelta mediante route model binding.
     * @return RedirectResponse Redirección al calendario con el resultado de la operación.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function changeStatus(Request $request, Cita $cita): RedirectResponse
    {
        $validated = $request->validate([
            'estado' => ['required', 'string', Rule::in(self::ESTADOS)],
        ]);

        $this->updateAppointmentStatus($cita, $validated['estado']);

        return redirect()
            ->route('citas.index')
            ->with('status', 'El estado de la cita se actualizó correctamente.');
    }

    /**
     * Aplica un estado dentro de una transacción y libera o reserva el horario según corresponda.
     *
     * @param  Cita  $cita  Cita que se actualizará.
     * @param  string  $estado  Estado previamente validado.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function updateAppointmentStatus(Cita $cita, string $estado): void
    {
        DB::transaction(function () use ($cita, $estado): void {
            $lockedCita = Cita::query()
                ->whereKey($cita->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $veterinarianId = (int) $lockedCita->veterinario_id;

            $this->lockVeterinarianRecord($veterinarianId);

            if ($estado !== 'cancelada') {
                $appointmentStart = CarbonImmutable::parse(
                    $lockedCita->fecha_cita->toDateTimeString(),
                    (string) config('app.timezone'),
                );

                $this->assertNoVeterinarianConflict(
                    $appointmentStart,
                    $veterinarianId,
                    (int) $lockedCita->getKey(),
                );
            }

            $lockedCita->update(['estado' => $estado]);
        });
    }

    /**
     * Bloquea el registro del veterinario para serializar las reservas de su agenda.
     *
     * @param  int  $veterinarianId  Identificador del veterinario que se bloqueará.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function lockVeterinarian(int $veterinarianId): void
    {
        $veterinarian = User::query()
            ->whereKey($veterinarianId)
            ->where('estado', true)
            ->whereHas(
                'role',
                fn (EloquentBuilder $query): EloquentBuilder => $query->where(
                    'nombre',
                    'Veterinario',
                ),
            )
            ->lockForUpdate()
            ->first();

        if ($veterinarian === null) {
            throw ValidationException::withMessages([
                'veterinario_id' => 'El veterinario seleccionado ya no está disponible.',
            ]);
        }
    }

    /**
     * Bloquea el usuario relacionado con una cita existente sin exigir que siga activo.
     *
     * @param  int  $veterinarianId  Identificador del veterinario relacionado.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function lockVeterinarianRecord(int $veterinarianId): void
    {
        User::query()
            ->whereKey($veterinarianId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Rechaza reservas cuyo intervalo de 30 minutos se cruza con otra cita activa.
     *
     * @param  CarbonImmutable  $appointmentStart  Inicio del horario que se desea reservar.
     * @param  int  $veterinarianId  Identificador del veterinario responsable.
     * @param  int|null  $exceptAppointmentId  Cita excluida al editar o cambiar un estado.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function assertNoVeterinarianConflict(
        CarbonImmutable $appointmentStart,
        int $veterinarianId,
        ?int $exceptAppointmentId = null,
    ): void {
        $conflictingAppointments = Cita::query()
            ->where('veterinario_id', $veterinarianId)
            ->where('estado', '!=', 'cancelada')
            ->where(
                'fecha_cita',
                '<',
                $appointmentStart
                    ->addMinutes(self::DURACION_CITA_MINUTOS)
                    ->format('Y-m-d H:i:s'),
            )
            ->where(
                'fecha_cita',
                '>',
                $appointmentStart
                    ->subMinutes(self::DURACION_CITA_MINUTOS)
                    ->format('Y-m-d H:i:s'),
            );

        if ($exceptAppointmentId !== null) {
            $conflictingAppointments->where('id', '!=', $exceptAppointmentId);
        }

        if ($conflictingAppointments->exists()) {
            throw ValidationException::withMessages([
                'fecha_cita' => 'El veterinario ya tiene una cita en ese horario.',
            ]);
        }
    }

    /**
     * Valida que el horario respete la jornada y los turnos de 30 minutos del calendario.
     *
     * @param  CarbonImmutable  $appointmentStart  Fecha y hora local validadas de la cita.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function assertAppointmentIsWithinBusinessHours(
        CarbonImmutable $appointmentStart,
    ): void {
        $appointmentTime = $appointmentStart->format('H:i');

        if (
            $appointmentStart->isPast()
            || $appointmentStart->isSunday()
            || ! in_array($appointmentStart->minute, [0, 30], true)
            || $appointmentTime < '07:00'
            || $appointmentTime >= '18:00'
        ) {
            throw ValidationException::withMessages([
                'fecha_cita' => 'La cita debe ser futura, de lunes a sábado, entre las 07:00 y las 17:30, en bloques de 30 minutos.',
            ]);
        }
    }

    /**
     * Limita la selección a usuarios activos con el rol de veterinario.
     *
     * @return Exists Regla de existencia para identificadores de veterinarios disponibles.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function veterinarianIdRule(): Exists
    {
        return Rule::exists('users', 'id')
            ->where(fn (QueryBuilder $query): QueryBuilder => $query
                ->where('estado', true)
                ->whereIn(
                    'rol_id',
                    Role::query()
                        ->select('id')
                        ->where('nombre', 'Veterinario'),
                ));
    }
}

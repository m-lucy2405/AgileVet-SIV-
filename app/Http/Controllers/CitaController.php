<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Mascota;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                'mascota_id' => $cita->mascota_id,
                'observaciones' => $cita->observaciones,
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
     * Devuelve las citas con el formato de eventos requerido por el calendario.
     *
     * @return JsonResponse Eventos con identificador, título, intervalo y estado.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function calendarEvents(): JsonResponse
    {
        $events = Cita::query()
            ->with('mascota:id,nombre')
            ->orderBy('fecha_cita')
            ->get()
            ->map(fn (Cita $cita): array => [
                'id' => $cita->id,
                'title' => "{$cita->mascota->nombre} - {$cita->motivo}",
                'start' => $cita->fecha_cita->toIso8601String(),
                'end' => $cita->fecha_cita
                    ->copy()
                    ->addMinutes(self::DURACION_CITA_MINUTOS)
                    ->toIso8601String(),
                'status' => $cita->estado,
            ]);

        return response()->json($events);
    }

    /**
     * Devuelve los recordatorios pendientes para una fecha determinada.
     *
     * @param  Request  $request  Petición con la fecha opcional en formato Y-m-d.
     * @return JsonResponse Fecha consultada y lista de recordatorios.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function dailyReminders(Request $request): JsonResponse
    {
        $date = $this->reminderDate($request);

        return response()->json([
            'fecha' => $date->toDateString(),
            'recordatorios' => $this->remindersForDate($date),
        ]);
    }

    /**
     * Renderiza la página de recordatorios con las citas del día solicitado.
     *
     * @param  Request  $request  Petición con la fecha opcional en formato Y-m-d.
     * @return View Página de recordatorios con las citas pendientes.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function remindersPage(Request $request): View
    {
        $date = $this->reminderDate($request);

        return view('citas.recordatorios', [
            'fecha' => $date,
            'recordatorios' => $this->remindersForDate($date),
        ]);
    }

    /**
     * Obtiene la fecha solicitada o, por defecto, el día siguiente.
     *
     * @param  Request  $request  Petición que puede incluir la fecha Y-m-d.
     * @return CarbonImmutable Fecha validada en la zona horaria de la aplicación.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function reminderDate(Request $request): CarbonImmutable
    {
        $validated = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return CarbonImmutable::parse(
            $validated['fecha'] ?? now()->addDay()->toDateString(),
            (string) config('app.timezone'),
        );
    }

    /**
     * Consulta y prepara los datos operativos de las citas pendientes para un día.
     *
     * @param  CarbonImmutable  $date  Día cuyas citas se incluirán en los recordatorios.
     * @return Collection<int, array{
     *     id: int,
     *     fecha_hora: string,
     *     hora: string,
     *     mascota: string,
     *     especie: string,
     *     cliente: string,
     *     telefono: string,
     *     motivo: string,
     *     veterinario: string
     * }> Recordatorios ordenados por hora.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function remindersForDate(CarbonImmutable $date): Collection
    {
        return Cita::query()
            ->with(['mascota.cliente', 'veterinario'])
            ->whereDate('fecha_cita', $date->toDateString())
            ->where('estado', 'pendiente')
            ->orderBy('fecha_cita')
            ->get()
            ->map(fn (Cita $cita): array => [
                'id' => $cita->id,
                'fecha_hora' => $cita->fecha_cita->format('Y-m-d H:i:s'),
                'hora' => $cita->fecha_cita->format('H:i'),
                'mascota' => $cita->mascota->nombre,
                'especie' => $cita->mascota->especie,
                'cliente' => $cita->mascota->cliente->nombre,
                'telefono' => $cita->mascota->cliente->telefono,
                'motivo' => $cita->motivo,
                'veterinario' => $cita->veterinario->nombre,
            ]);
    }

    /**
     * Valida y registra una cita si el veterinario y la mascota tienen disponible el horario.
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
            $mascotaId = (int) $validated['mascota_id'];

            // Se bloquea siempre en el mismo orden (veterinario y luego mascota)
            // para serializar reservas simultáneas sin provocar bloqueos cruzados.
            $this->lockVeterinarian($veterinarianId);
            $this->lockMascota($mascotaId);

            $this->assertNoVeterinarianConflict($appointmentStart, $veterinarianId);
            $this->assertNoPetConflict($appointmentStart, $mascotaId);

            Cita::query()->create([
                'mascota_id' => $mascotaId,
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
            $mascotaId = (int) $validated['mascota_id'];

            $this->lockVeterinarian($veterinarianId);
            $this->lockMascota($mascotaId);

            if ($lockedCita->estado !== 'cancelada') {
                $exceptId = (int) $lockedCita->getKey();

                $this->assertNoVeterinarianConflict($appointmentStart, $veterinarianId, $exceptId);
                $this->assertNoPetConflict($appointmentStart, $mascotaId, $exceptId);
            }

            $lockedCita->update([
                'mascota_id' => $mascotaId,
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
            $mascotaId = (int) $lockedCita->mascota_id;

            $this->lockVeterinarianRecord($veterinarianId);
            $this->lockMascota($mascotaId);

            if ($estado !== 'cancelada') {
                $appointmentStart = CarbonImmutable::parse(
                    $lockedCita->fecha_cita->toDateTimeString(),
                    (string) config('app.timezone'),
                );
                $exceptId = (int) $lockedCita->getKey();

                $this->assertNoVeterinarianConflict($appointmentStart, $veterinarianId, $exceptId);
                $this->assertNoPetConflict($appointmentStart, $mascotaId, $exceptId);
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
     * Bloquea el registro de la mascota para serializar las reservas de su agenda.
     *
     * @param  int  $mascotaId  Identificador de la mascota que se bloqueará.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function lockMascota(int $mascotaId): void
    {
        Mascota::query()
            ->whereKey($mascotaId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Rechaza reservas cuyo intervalo se cruza con otra cita activa del mismo veterinario.
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
        if ($this->hasActiveConflict('veterinario_id', $veterinarianId, $appointmentStart, $exceptAppointmentId)) {
            throw ValidationException::withMessages([
                'fecha_cita' => 'El veterinario ya tiene una cita en ese horario.',
            ]);
        }
    }

    /**
     * Rechaza reservas cuyo intervalo se cruza con otra cita activa de la misma mascota.
     *
     * @param  CarbonImmutable  $appointmentStart  Inicio del horario que se desea reservar.
     * @param  int  $mascotaId  Identificador de la mascota atendida.
     * @param  int|null  $exceptAppointmentId  Cita excluida al editar o cambiar un estado.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function assertNoPetConflict(
        CarbonImmutable $appointmentStart,
        int $mascotaId,
        ?int $exceptAppointmentId = null,
    ): void {
        if ($this->hasActiveConflict('mascota_id', $mascotaId, $appointmentStart, $exceptAppointmentId)) {
            throw ValidationException::withMessages([
                'mascota_id' => 'La mascota ya tiene una cita en ese horario.',
            ]);
        }
    }

    /**
     * Indica si existe una cita activa que se cruce con el intervalo solicitado.
     *
     * @param  string  $column  Columna por la que se busca el choque (veterinario_id o mascota_id).
     * @param  int  $id  Valor de la columna para el veterinario o la mascota.
     * @param  CarbonImmutable  $appointmentStart  Inicio del horario que se desea reservar.
     * @param  int|null  $exceptAppointmentId  Cita excluida al editar o cambiar un estado.
     * @return bool Verdadero si el horario ya está ocupado.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function hasActiveConflict(
        string $column,
        int $id,
        CarbonImmutable $appointmentStart,
        ?int $exceptAppointmentId = null,
    ): bool {
        return Cita::query()
            ->where($column, $id)
            ->where('estado', '!=', 'cancelada')
            ->when(
                $exceptAppointmentId !== null,
                fn (EloquentBuilder $query): EloquentBuilder => $query->where('id', '!=', $exceptAppointmentId),
            )
            ->overlappingInterval(
                $appointmentStart,
                $appointmentStart->addMinutes(self::DURACION_CITA_MINUTOS),
                self::DURACION_CITA_MINUTOS,
            )
            ->exists();
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
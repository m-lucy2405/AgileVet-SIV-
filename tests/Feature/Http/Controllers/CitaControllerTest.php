<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Mascota;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitaControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rechaza una reserva que se cruza con otra cita del mismo veterinario.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_store_rejects_overlapping_appointment_for_same_veterinarian(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $payload = $this->appointmentPayload($veterinarian, $mascota, '2026-10-22T10:00');

        $this->actingAs($veterinarian)
            ->post(route('citas.store'), $payload)
            ->assertRedirect(route('citas.index'));

        $response = $this->post(route('citas.store'), $payload);

        $response->assertSessionHasErrors([
            'fecha_cita' => 'El veterinario ya tiene una cita en ese horario.',
        ]);
        $this->assertDatabaseCount('citas', 1);
    }

    /**
     * Rechaza una cita que comienza antes del fin de una cita existente.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_store_rejects_overlap_when_existing_appointment_starts_first(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $this->createCita($veterinarian, $mascota, '2026-10-22 09:45:00');

        $response = $this->actingAs($veterinarian)
            ->post(
                route('citas.store'),
                $this->appointmentPayload(
                    $veterinarian,
                    $mascota,
                    '2026-10-22T10:00',
                ),
            );

        $response->assertSessionHasErrors([
            'fecha_cita' => 'El veterinario ya tiene una cita en ese horario.',
        ]);
        $this->assertDatabaseCount('citas', 1);
    }

    /**
     * Rechaza una cita que comienza dentro del intervalo de una cita existente.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_store_rejects_overlap_when_new_appointment_starts_first(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $this->createCita($veterinarian, $mascota, '2026-10-22 10:15:00');

        $response = $this->actingAs($veterinarian)
            ->post(
                route('citas.store'),
                $this->appointmentPayload(
                    $veterinarian,
                    $mascota,
                    '2026-10-22T10:00',
                ),
            );

        $response->assertSessionHasErrors([
            'fecha_cita' => 'El veterinario ya tiene una cita en ese horario.',
        ]);
        $this->assertDatabaseCount('citas', 1);
    }

    /**
     * Permite reservar la misma hora para veterinarios diferentes.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_store_allows_same_time_for_different_veterinarians(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $firstVeterinarian = $this->createVeterinarian();
        $secondVeterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $payload = $this->appointmentPayload(
            $firstVeterinarian,
            $mascota,
            '2026-10-22T10:00',
        );

        $this->actingAs($firstVeterinarian)
            ->post(route('citas.store'), $payload)
            ->assertRedirect(route('citas.index'));

        $payload['veterinario_id'] = $secondVeterinarian->id;
        $response = $this->post(route('citas.store'), $payload);

        $response->assertRedirect(route('citas.index'));
        $this->assertDatabaseCount('citas', 2);
    }

    /**
     * Permite reservar turnos contiguos de 30 minutos para el mismo veterinario.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_store_allows_adjacent_thirty_minute_slots_for_same_veterinarian(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $payload = $this->appointmentPayload($veterinarian, $mascota, '2026-10-22T10:00');

        $this->actingAs($veterinarian)
            ->post(route('citas.store'), $payload)
            ->assertRedirect(route('citas.index'));

        $payload['fecha_cita'] = '2026-10-22T10:30';
        $response = $this->post(route('citas.store'), $payload);

        $response->assertRedirect(route('citas.index'));
        $this->assertDatabaseCount('citas', 2);
    }

    /**
     * Actualiza los datos de una cita cuando el nuevo horario está disponible.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_update_saves_changes_when_new_schedule_is_available(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cita = $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');
        $payload = $this->appointmentPayload(
            $veterinarian,
            $mascota,
            '2026-10-22T11:00',
        );
        $payload['motivo'] = 'Consulta de seguimiento';

        $response = $this->actingAs($veterinarian)
            ->put(route('citas.update', $cita), $payload);

        $response->assertRedirect(route('citas.index'));
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'fecha_cita' => '2026-10-22 11:00:00',
            'motivo' => 'Consulta de seguimiento',
        ]);
    }

    /**
     * Rechaza la actualización si el nuevo horario se cruza con otra cita.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_update_rejects_schedule_conflict_and_preserves_existing_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cita = $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');
        $this->createCita($veterinarian, $mascota, '2026-10-22 10:30:00');
        $payload = $this->appointmentPayload(
            $veterinarian,
            $mascota,
            '2026-10-22T10:30',
        );

        $response = $this->actingAs($veterinarian)
            ->put(route('citas.update', $cita), $payload);

        $response->assertSessionHasErrors([
            'fecha_cita' => 'El veterinario ya tiene una cita en ese horario.',
        ]);
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'fecha_cita' => '2026-10-22 10:00:00',
        ]);
    }

    /**
     * Cancela sin eliminar la cita y permite reservar nuevamente su horario.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_destroy_cancels_appointment_and_releases_its_schedule(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cita = $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');

        $this->actingAs($veterinarian)
            ->delete(route('citas.destroy', $cita))
            ->assertRedirect(route('citas.index'));

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'cancelada',
        ]);

        $response = $this->post(
            route('citas.store'),
            $this->appointmentPayload($veterinarian, $mascota, '2026-10-22T10:00'),
        );

        $response->assertRedirect(route('citas.index'));
        $this->assertDatabaseCount('citas', 2);
    }

    /**
     * Permite cancelar una cita aunque el veterinario asignado esté desactivado.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_destroy_cancels_appointment_for_inactive_veterinarian(): void
    {
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cita = $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');
        $veterinarian->update(['estado' => false]);

        $response = $this->actingAs($veterinarian)
            ->delete(route('citas.destroy', $cita));

        $response->assertRedirect(route('citas.index'));
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'cancelada',
        ]);
    }

    /**
     * Cambia el estado de una cita a uno de los estados dinámicos permitidos.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_change_status_updates_appointment_state(): void
    {
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cita = $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');

        $response = $this->actingAs($veterinarian)
            ->patch(route('citas.estado', $cita), ['estado' => 'no_asistio']);

        $response->assertRedirect(route('citas.index'));
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'no_asistio',
        ]);
    }

    /**
     * No permite reactivar una cita cancelada sobre un horario ya reservado.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_change_status_rejects_reactivation_when_schedule_is_occupied(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cancelledAppointment = $this->createCita(
            $veterinarian,
            $mascota,
            '2026-10-22 10:00:00',
            'cancelada',
        );
        $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');

        $response = $this->actingAs($veterinarian)
            ->patch(
                route('citas.estado', $cancelledAppointment),
                ['estado' => 'pendiente'],
            );

        $response->assertSessionHasErrors([
            'fecha_cita' => 'El veterinario ya tiene una cita en ese horario.',
        ]);
        $this->assertDatabaseHas('citas', [
            'id' => $cancelledAppointment->id,
            'estado' => 'cancelada',
        ]);
    }

    /**
     * Rechaza estados que no pertenecen al catálogo del ciclo de vida de la cita.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_change_status_rejects_unknown_state(): void
    {
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cita = $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');

        $response = $this->actingAs($veterinarian)
            ->patch(route('citas.estado', $cita), ['estado' => 'desconocida']);

        $response->assertSessionHasErrors('estado');
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'pendiente',
        ]);
    }

    /**
     * Rechaza una cita fuera del horario de atención configurado.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_store_rejects_appointment_outside_business_hours(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();

        $response = $this->actingAs($veterinarian)
            ->post(
                route('citas.store'),
                $this->appointmentPayload(
                    $veterinarian,
                    $mascota,
                    '2026-10-22T18:00',
                ),
            );

        $response->assertSessionHasErrors([
            'fecha_cita' => 'La cita debe ser futura, de lunes a sábado, entre las 07:00 y las 17:30, en bloques de 30 minutos.',
        ]);
        $this->assertDatabaseCount('citas', 0);
    }

    /**
     * Devuelve eventos del calendario con campos y fechas ISO 8601.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_calendar_events_returns_fullcalendar_json(): void
    {
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cita = $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');

        $response = $this->actingAs($veterinarian)
            ->getJson(route('citas.calendario.eventos'));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $cita->id)
            ->assertJsonPath('0.title', 'Luna - Consulta de prueba')
            ->assertJsonPath('0.start', '2026-10-22T10:00:00+00:00')
            ->assertJsonPath('0.end', '2026-10-22T10:30:00+00:00')
            ->assertJsonPath('0.status', 'pendiente');
    }

    /**
     * Devuelve únicamente recordatorios pendientes del día consultado.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_daily_reminders_filters_by_date_and_pending_status(): void
    {
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $cita = $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');
        $this->createCita(
            $veterinarian,
            $mascota,
            '2026-10-22 11:00:00',
            'cancelada',
        );
        $this->createCita($veterinarian, $mascota, '2026-10-23 10:00:00');

        $response = $this->actingAs($veterinarian)
            ->getJson(route('citas.recordatorios.datos', ['fecha' => '2026-10-22']));

        $response->assertOk()
            ->assertJsonPath('fecha', '2026-10-22')
            ->assertJsonCount(1, 'recordatorios')
            ->assertJsonPath('recordatorios.0.id', $cita->id)
            ->assertJsonPath('recordatorios.0.fecha_hora', '2026-10-22 10:00:00')
            ->assertJsonPath('recordatorios.0.hora', '10:00')
            ->assertJsonPath('recordatorios.0.cliente', 'Cliente de prueba')
            ->assertJsonPath('recordatorios.0.telefono', '70000000');
    }

    /**
     * Usa el día siguiente como fecha predeterminada para los recordatorios.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_daily_reminders_default_to_tomorrow(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-20 06:00:00'));
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $this->createCita($veterinarian, $mascota, '2026-10-21 10:00:00');
        $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');

        $response = $this->actingAs($veterinarian)
            ->getJson(route('citas.recordatorios.datos'));

        $response->assertOk()
            ->assertJsonPath('fecha', '2026-10-21')
            ->assertJsonCount(1, 'recordatorios');
    }

    /**
     * Rechaza fechas de recordatorios con un formato distinto a Y-m-d.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_daily_reminders_reject_invalid_date_format(): void
    {
        $veterinarian = $this->createVeterinarian();

        $response = $this->actingAs($veterinarian)
            ->getJson(route('citas.recordatorios.datos', ['fecha' => '22-10-2026']));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('fecha');
    }

    /**
     * Requiere autenticación para consultar los endpoints JSON de citas.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_json_appointment_endpoints_require_authentication(): void
    {
        $this->getJson(route('citas.calendario.eventos'))->assertUnauthorized();
        $this->getJson(route('citas.recordatorios.datos'))->assertUnauthorized();
    }

    /**
     * Renderiza la página con las citas pendientes de la fecha seleccionada.
     *
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function test_reminders_page_renders_appointments_for_selected_date(): void
    {
        $veterinarian = $this->createVeterinarian();
        $mascota = $this->createMascota();
        $this->createCita($veterinarian, $mascota, '2026-10-22 10:00:00');

        $response = $this->actingAs($veterinarian)
            ->get(route('citas.recordatorios', ['fecha' => '2026-10-22']));

        $response->assertOk()
            ->assertSee('Luna')
            ->assertSee('Cliente de prueba')
            ->assertSee('70000000');
    }

    /**
     * Crea un usuario activo con el rol de veterinario para la prueba actual.
     *
     * @return User Veterinario autenticable persistido en la base de pruebas.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function createVeterinarian(): User
    {
        $role = Role::query()->firstOrCreate(
            ['nombre' => 'Veterinario'],
            ['descripcion' => 'Usuario veterinario para pruebas.'],
        );

        return User::factory()->create(['rol_id' => $role->id]);
    }

    /**
     * Crea una mascota con su cliente propietario para la prueba actual.
     *
     * @return Mascota Mascota persistida en la base de pruebas.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function createMascota(): Mascota
    {
        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente de prueba',
            'telefono' => '70000000',
            'direccion' => 'San Salvador',
            'email' => 'cliente@example.com',
        ]);

        return Mascota::query()->create([
            'cliente_id' => $cliente->id,
            'nombre' => 'Luna',
            'especie' => 'Canino',
            'sexo' => 'Hembra',
            'esterilizado' => false,
        ]);
    }

    /**
     * Crea una cita existente para preparar escenarios de actualización o cancelación.
     *
     * @param  User  $veterinarian  Veterinario asignado a la cita.
     * @param  Mascota  $mascota  Mascota atendida en la cita.
     * @param  string  $fechaHora  Fecha y hora persistidas en formato SQL.
     * @param  string  $estado  Estado inicial de la cita.
     * @return Cita Cita persistida en la base de pruebas.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function createCita(
        User $veterinarian,
        Mascota $mascota,
        string $fechaHora,
        string $estado = 'pendiente',
    ): Cita {
        return Cita::query()->create([
            'mascota_id' => $mascota->id,
            'veterinario_id' => $veterinarian->id,
            'fecha_cita' => $fechaHora,
            'estado' => $estado,
            'motivo' => 'Consulta de prueba',
        ]);
    }

    /**
     * Prepara los datos comunes de una solicitud de creación o actualización.
     *
     * @param  User  $veterinarian  Veterinario seleccionado para la cita.
     * @param  Mascota  $mascota  Mascota seleccionada para la cita.
     * @param  string  $fechaHora  Fecha y hora en formato de datetime-local.
     * @return array<string, int|string> Datos de cita aceptados por el controlador.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function appointmentPayload(
        User $veterinarian,
        Mascota $mascota,
        string $fechaHora,
    ): array {
        return [
            'mascota_id' => $mascota->id,
            'veterinario_id' => $veterinarian->id,
            'fecha_cita' => $fechaHora,
            'motivo' => 'Consulta general',
            'observaciones' => '',
        ];
    }
}

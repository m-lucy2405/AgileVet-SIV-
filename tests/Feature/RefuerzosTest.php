<?php

namespace Tests\Feature;

use App\Models\CatalogoDesparasitante;
use App\Models\CatalogoVacuna;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Desparasitacion;
use App\Models\Mascota;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacunacion;
use App\Services\AgendaRefuerzoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de los refuerzos automáticos: servicio de agenda, formularios de
 * vacunas y desparasitaciones con catálogo, y pantalla del catálogo.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class RefuerzosTest extends TestCase
{
    use RefreshDatabase;

    private AgendaRefuerzoService $agenda;

    protected function setUp(): void
    {
        parent::setUp();

        // Lunes 16 de noviembre de 2026, 10:00
        $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00:00'));
        $this->agenda = app(AgendaRefuerzoService::class);
    }

    /* ------------------------------------------------------------------
     | Servicio de agenda
     * ------------------------------------------------------------------ */

    public function test_sunday_is_moved_to_saturday_at_first_hour(): void
    {
        $cita = $this->agenda->agendar($this->createMascota(), $this->createUser('Veterinario'),
            CarbonImmutable::parse('2026-12-06'), 'Refuerzo'); // domingo

        $this->assertSame('2026-12-05 07:00', $cita->fecha_cita->format('Y-m-d H:i')); // sábado
        $this->assertSame('pendiente', $cita->estado);
    }

    public function test_occupied_hour_of_the_veterinarian_takes_the_next_one(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $this->createCita($this->createMascota('Michi'), $veterinario, '2026-12-05 07:00:00');

        $cita = $this->agenda->agendar($this->createMascota(), $veterinario, CarbonImmutable::parse('2026-12-05'), 'Refuerzo');

        $this->assertSame('2026-12-05 07:30', $cita->fecha_cita->format('Y-m-d H:i'));
    }

    public function test_occupied_hour_of_the_pet_takes_the_next_one(): void
    {
        $mascota = $this->createMascota();
        $this->createCita($mascota, $this->createUser('Veterinario'), '2026-12-05 07:00:00'); // con otro veterinario

        $cita = $this->agenda->agendar($mascota, $this->createUser('Veterinario'), CarbonImmutable::parse('2026-12-05'), 'Refuerzo');

        $this->assertSame('2026-12-05 07:30', $cita->fecha_cita->format('Y-m-d H:i'));
    }

    public function test_full_day_moves_to_the_previous_working_day(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $otra = $this->createMascota('Michi');

        // Se llena el sábado 05/12 de 07:00 a 17:30
        for ($hora = CarbonImmutable::parse('2026-12-05 07:00'); $hora->lte(CarbonImmutable::parse('2026-12-05 17:30')); $hora = $hora->addMinutes(30)) {
            $this->createCita($otra, $veterinario, $hora->toDateTimeString());
        }

        $cita = $this->agenda->agendar($this->createMascota(), $veterinario, CarbonImmutable::parse('2026-12-05'), 'Refuerzo');

        $this->assertSame('2026-12-04 07:00', $cita->fecha_cita->format('Y-m-d H:i')); // viernes
    }

    public function test_cancelled_appointments_do_not_block_the_hour(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $this->createCita($this->createMascota('Michi'), $veterinario, '2026-12-05 07:00:00', 'cancelada');

        $cita = $this->agenda->agendar($this->createMascota(), $veterinario, CarbonImmutable::parse('2026-12-05'), 'Refuerzo');

        $this->assertSame('2026-12-05 07:00', $cita->fecha_cita->format('Y-m-d H:i'));
    }

    public function test_past_or_today_date_does_not_create_an_appointment(): void
    {
        $cita = $this->agenda->agendar($this->createMascota(), $this->createUser('Veterinario'), CarbonImmutable::today(), 'Refuerzo');

        $this->assertNull($cita);
        $this->assertSame(0, Cita::query()->count());
    }

    /* ------------------------------------------------------------------
     | Formularios
     * ------------------------------------------------------------------ */

    public function test_vaccine_from_catalog_calculates_date_and_schedules_booster(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();
        $vacuna = CatalogoVacuna::query()->create(['nombre' => 'Parvovirus (cachorro)', 'especie' => 'Canino', 'intervalo_dias' => 21, 'activo' => true]);

        $this->actingAs($veterinario)
            ->post(route('vacunaciones.store', $mascota), [
                'catalogo_vacuna_id' => $vacuna->id,
                'fecha_aplicacion' => '2026-11-16',
                'agendar_refuerzo' => '1',
            ])
            ->assertRedirect(route('expedientes.show', $mascota))
            ->assertSessionHas('status', 'La vacuna se registró correctamente. Se agendó el refuerzo para el lunes 07/12/2026 a las 07:00.');

        $registro = Vacunacion::query()->firstOrFail();
        $this->assertSame('Parvovirus (cachorro)', $registro->nombre_vacuna); // nombre copiado del catálogo
        $this->assertSame('2026-12-07', $registro->proxima_dosis->toDateString()); // 16/11 + 21 días

        $cita = $registro->citaRefuerzo;
        $this->assertNotNull($cita);
        $this->assertSame('Refuerzo de Parvovirus (cachorro)', $cita->motivo);
        $this->assertSame($veterinario->id, $cita->veterinario_id);
        $this->assertSame('2026-12-07 07:00', $cita->fecha_cita->format('Y-m-d H:i'));
    }

    public function test_without_the_checkbox_no_appointment_is_created(): void
    {
        $mascota = $this->createMascota();
        $vacuna = CatalogoVacuna::query()->create(['nombre' => 'Antirrábica', 'intervalo_dias' => 365, 'activo' => true]);

        $this->actingAs($this->createUser('Veterinario'))
            ->post(route('vacunaciones.store', $mascota), [
                'catalogo_vacuna_id' => $vacuna->id,
                'fecha_aplicacion' => '2026-11-16',
                'agendar_refuerzo' => '0',
            ])
            ->assertSessionHas('status', 'La vacuna se registró correctamente.');

        $this->assertSame('2027-11-16', Vacunacion::query()->first()->proxima_dosis->toDateString());
        $this->assertSame(0, Cita::query()->count());
    }

    public function test_deworming_uses_suggested_dose_and_moves_sunday_to_saturday(): void
    {
        $mascota = $this->createMascota();
        $producto = CatalogoDesparasitante::query()->create([
            'nombre' => 'Albendazol (interno)', 'dosis_sugerida' => '1 tableta por cada 10 kg', 'intervalo_dias' => 90, 'activo' => true,
        ]);

        // 16/11/2026 + 90 días = domingo 14/02/2027 → sábado 13/02/2027
        $this->actingAs($this->createUser('Veterinario'))
            ->post(route('desparasitaciones.store', $mascota), [
                'catalogo_desparasitante_id' => $producto->id,
                'fecha_aplicacion' => '2026-11-16',
                'agendar_refuerzo' => '1',
            ])
            ->assertSessionHas('status', 'La desparasitación se registró correctamente. Se agendó la próxima aplicación para el sábado 13/02/2027 a las 07:00.');

        $registro = Desparasitacion::query()->firstOrFail();
        $this->assertSame('1 tableta por cada 10 kg', $registro->dosis);
        $this->assertSame('2027-02-14', $registro->proxima_aplicacion->toDateString());
        $this->assertSame('2027-02-13 07:00', $registro->citaRefuerzo->fecha_cita->format('Y-m-d H:i'));
    }

    public function test_form_only_lists_active_catalog_for_the_pet_species(): void
    {
        CatalogoVacuna::query()->create(['nombre' => 'Antirrábica', 'intervalo_dias' => 365, 'activo' => true]);
        CatalogoVacuna::query()->create(['nombre' => 'Séxtuple canina', 'especie' => 'Canino', 'intervalo_dias' => 365, 'activo' => true]);
        CatalogoVacuna::query()->create(['nombre' => 'Triple felina', 'especie' => 'Felino', 'intervalo_dias' => 365, 'activo' => true]);
        CatalogoVacuna::query()->create(['nombre' => 'Vacuna retirada', 'intervalo_dias' => 365, 'activo' => false]);

        $this->actingAs($this->createUser('Veterinario'))
            ->get(route('vacunaciones.create', $this->createMascota()))
            ->assertOk()
            ->assertViewHas('catalogo', fn ($catalogo): bool => $catalogo->pluck('nombre')->all() === ['Antirrábica', 'Séxtuple canina']);
    }

    public function test_inactive_catalog_vaccine_is_rejected(): void
    {
        $vacuna = CatalogoVacuna::query()->create(['nombre' => 'Vacuna retirada', 'intervalo_dias' => 365, 'activo' => false]);

        $this->actingAs($this->createUser('Veterinario'))
            ->post(route('vacunaciones.store', $this->createMascota()), [
                'catalogo_vacuna_id' => $vacuna->id,
                'fecha_aplicacion' => '2026-11-16',
            ])
            ->assertSessionHasErrors(['catalogo_vacuna_id' => 'Elige una vacuna válida del catálogo.']);

        $this->assertSame(0, Vacunacion::query()->count());
    }

    /* ------------------------------------------------------------------
     | Pantalla del catálogo
     * ------------------------------------------------------------------ */

    public function test_administrator_updates_the_interval_and_names_are_unique(): void
    {
        $vacuna = CatalogoVacuna::query()->create(['nombre' => 'Antirrábica', 'intervalo_dias' => 365, 'activo' => true]);
        CatalogoVacuna::query()->create(['nombre' => 'Leptospirosis', 'intervalo_dias' => 180, 'activo' => true]);

        $this->actingAs($this->createUser('Administrador'));

        $this->put(route('catalogos.update', ['vacunas', $vacuna->id]), ['nombre' => 'Antirrábica', 'intervalo_dias' => 360])
            ->assertRedirect(route('catalogos.index', 'vacunas'));
        $this->assertSame(360, $vacuna->fresh()->intervalo_dias);

        $this->put(route('catalogos.update', ['vacunas', $vacuna->id]), ['nombre' => 'Leptospirosis', 'intervalo_dias' => 360])
            ->assertSessionHasErrors(['nombre' => 'Ya existe un registro con ese nombre en el catálogo.']);
    }

    public function test_only_the_administrator_can_manage_the_catalog(): void
    {
        $vacuna = CatalogoVacuna::query()->create(['nombre' => 'Antirrábica', 'intervalo_dias' => 365, 'activo' => true]);

        foreach (['Recepcionista', 'Veterinario'] as $rol) {
            $this->actingAs($this->createUser($rol));

            $this->get(route('catalogos.index', 'vacunas'))->assertForbidden();
            $this->patch(route('catalogos.estado', ['vacunas', $vacuna->id]))->assertForbidden();
        }

        $this->assertTrue($vacuna->fresh()->activo);
    }

    /* ------------------------------------------------------------------
     | Ayudantes
     * ------------------------------------------------------------------ */

    private function createUser(string $rol): User
    {
        $role = Role::query()->firstOrCreate(['nombre' => $rol], ['descripcion' => "Rol {$rol} para pruebas."]);

        return User::factory()->create(['rol_id' => $role->id]);
    }

    private function createMascota(string $nombre = 'Firulais'): Mascota
    {
        $cliente = Cliente::query()->create(['nombre' => 'Cliente de prueba', 'telefono' => '70000000', 'direccion' => 'San Miguel']);

        return Mascota::query()->create([
            'cliente_id' => $cliente->id,
            'nombre' => $nombre,
            'especie' => 'Canino',
            'sexo' => 'macho',
            'esterilizado' => false,
        ]);
    }

    private function createCita(Mascota $mascota, User $veterinario, string $fecha, string $estado = 'pendiente'): Cita
    {
        return Cita::query()->create([
            'mascota_id' => $mascota->id,
            'veterinario_id' => $veterinario->id,
            'fecha_cita' => $fecha,
            'estado' => $estado,
            'motivo' => 'Control',
        ]);
    }
}
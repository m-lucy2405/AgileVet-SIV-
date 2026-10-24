<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Consulta;
use App\Models\Mascota;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del módulo de expedientes clínicos: lista, expediente, edición de datos,
 * consultas, vacunas y desparasitaciones.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class ExpedienteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fecha fija para que las validaciones de fechas no dependan del día en que se ejecutan
        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00'));
    }

    /* ------------------------------------------------------------------
     | Lista y expediente
     * ------------------------------------------------------------------ */

    public function test_index_lists_pets_and_filters_by_search(): void
    {
        $this->createMascota('Firulais', 'Ana Rivera');
        $this->createMascota('Michi', 'José Martínez');

        $this->actingAs($this->createUser('Recepcionista'))
            ->get(route('expedientes.index'))
            ->assertOk()
            ->assertSee('Firulais')
            ->assertSee('Michi');

        $this->get(route('expedientes.index', ['buscar' => 'Ana']))
            ->assertOk()
            ->assertSee('Firulais')
            ->assertDontSee('Michi');
    }

    public function test_show_displays_allergies_and_hides_clinical_buttons_for_receptionist(): void
    {
        $mascota = $this->createMascota();
        $mascota->expediente()->create(['fecha_apertura' => now(), 'alergias' => 'Penicilina']);

        $this->actingAs($this->createUser('Recepcionista'))
            ->get(route('expedientes.show', $mascota))
            ->assertOk()
            ->assertSee('Penicilina')
            ->assertDontSee('Registrar consulta')
            ->assertDontSee('+ Registrar')
            ->assertDontSee('Editar datos');
    }

    public function test_show_displays_register_buttons_for_veterinarian(): void
    {
        $mascota = $this->createMascota();
        $mascota->expediente()->create(['fecha_apertura' => now()]);

        $this->actingAs($this->createUser('Veterinario'))
            ->get(route('expedientes.show', $mascota))
            ->assertOk()
            ->assertSee('Registrar consulta')
            ->assertSee('+ Registrar')
            ->assertSee('Editar datos');
    }

    public function test_expediente_pages_require_authentication(): void
    {
        $mascota = $this->createMascota();

        $this->get(route('expedientes.index'))->assertRedirect(route('login'));
        $this->get(route('expedientes.show', $mascota))->assertRedirect(route('login'));
    }

    /* ------------------------------------------------------------------
     | Consultas
     * ------------------------------------------------------------------ */

    public function test_veterinarian_registers_consulta_and_opens_expediente(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();

        $response = $this->actingAs($veterinario)->post(route('consultas.store', $mascota), [
            'motivo' => 'Vómito',
            'peso_kg' => '12.5',
            'temperatura_c' => '38.6',
            'diagnostico' => 'Gastritis leve',
            'alergias' => 'Penicilina',
            'tratamientos' => [
                ['medicamento' => 'Omeprazol', 'dosis' => '1 tableta', 'frecuencia' => 'cada 24 horas', 'duracion' => '5 días'],
                ['medicamento' => '', 'dosis' => '', 'frecuencia' => '', 'duracion' => ''], // fila vacía: se descarta
            ],
        ]);

        $response->assertRedirect(route('expedientes.show', $mascota))
            ->assertSessionHas('status', 'La consulta se registró correctamente.');

        $this->assertDatabaseHas('expedientes', ['mascota_id' => $mascota->id, 'alergias' => 'Penicilina']);
        $this->assertDatabaseHas('consultas', [
            'veterinario_id' => $veterinario->id,
            'motivo' => 'Vómito',
            'diagnostico' => 'Gastritis leve',
        ]);
        $this->assertDatabaseCount('tratamientos', 1);
        $this->assertDatabaseHas('tratamientos', ['medicamento' => 'Omeprazol']);
    }

    public function test_second_consulta_reuses_existing_expediente(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();

        $this->actingAs($veterinario);
        $this->post(route('consultas.store', $mascota), ['motivo' => 'Control', 'diagnostico' => 'Sano']);
        $this->post(route('consultas.store', $mascota), ['motivo' => 'Control', 'diagnostico' => 'Sano']);

        $this->assertDatabaseCount('expedientes', 1);
        $this->assertDatabaseCount('consultas', 2);
    }

    public function test_receptionist_cannot_register_consulta(): void
    {
        $mascota = $this->createMascota();

        $this->actingAs($this->createUser('Recepcionista'));

        $this->get(route('consultas.create', $mascota))->assertForbidden();
        $this->post(route('consultas.store', $mascota), ['motivo' => 'Control', 'diagnostico' => 'Sano'])
            ->assertForbidden();

        $this->assertDatabaseCount('consultas', 0);
    }

    public function test_consulta_requires_diagnostico(): void
    {
        $mascota = $this->createMascota();

        $this->actingAs($this->createUser('Veterinario'))
            ->post(route('consultas.store', $mascota), ['motivo' => 'Control'])
            ->assertSessionHasErrors(['diagnostico' => 'El diagnóstico es obligatorio.']);

        $this->assertDatabaseCount('consultas', 0);
        $this->assertDatabaseCount('expedientes', 0);
    }

    public function test_partial_medication_row_requires_all_fields(): void
    {
        $mascota = $this->createMascota();

        $this->actingAs($this->createUser('Veterinario'))
            ->post(route('consultas.store', $mascota), [
                'motivo' => 'Control',
                'diagnostico' => 'Sano',
                'tratamientos' => [
                    ['medicamento' => 'Amoxicilina', 'dosis' => '', 'frecuencia' => '', 'duracion' => ''],
                ],
            ])
            ->assertSessionHasErrors(['tratamientos.0.dosis' => 'Escribe la dosis.']);

        $this->assertDatabaseCount('consultas', 0);
    }

    public function test_consulta_from_cita_marks_it_attended_and_cannot_repeat(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();
        $cita = Cita::query()->create([
            'mascota_id' => $mascota->id,
            'veterinario_id' => $veterinario->id,
            'fecha_cita' => '2026-10-20 10:00:00',
            'estado' => 'pendiente',
            'motivo' => 'Vacunación',
        ]);

        $datos = ['cita_id' => $cita->id, 'motivo' => 'Vacunación', 'diagnostico' => 'Apto para vacunar'];

        $this->actingAs($veterinario)
            ->post(route('consultas.store', $mascota), $datos)
            ->assertRedirect(route('expedientes.show', $mascota));

        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => 'atendida']);
        $this->assertDatabaseHas('consultas', ['cita_id' => $cita->id]);

        // La misma cita no puede generar una segunda consulta
        $this->post(route('consultas.store', $mascota), $datos)->assertSessionHasErrors('cita_id');
        $this->assertSame(1, Consulta::query()->where('cita_id', $cita->id)->count());
    }

    /* ------------------------------------------------------------------
     | Vacunas y desparasitaciones
     * ------------------------------------------------------------------ */

    public function test_veterinarian_registers_vacuna(): void
    {
        $mascota = $this->createMascota();

        $this->actingAs($this->createUser('Veterinario'))
            ->post(route('vacunaciones.store', $mascota), [
                'nombre_vacuna' => 'Antirrábica',
                'fecha_aplicacion' => '2026-10-20',
                'proxima_dosis' => '2027-10-20',
            ])
            ->assertRedirect(route('expedientes.show', $mascota))
            ->assertSessionHas('status', 'La vacuna se registró correctamente.');

        $this->assertDatabaseCount('expedientes', 1);
        $this->assertDatabaseHas('vacunaciones', ['nombre_vacuna' => 'Antirrábica']);
    }

    public function test_vacuna_rejects_future_application_and_earlier_next_dose(): void
    {
        $mascota = $this->createMascota();
        $this->actingAs($this->createUser('Veterinario'));

        $this->post(route('vacunaciones.store', $mascota), [
            'nombre_vacuna' => 'Antirrábica',
            'fecha_aplicacion' => '2026-10-25',
        ])->assertSessionHasErrors(['fecha_aplicacion' => 'La fecha de aplicación no puede ser futura.']);

        $this->post(route('vacunaciones.store', $mascota), [
            'nombre_vacuna' => 'Antirrábica',
            'fecha_aplicacion' => '2026-10-19',
            'proxima_dosis' => '2026-10-18',
        ])->assertSessionHasErrors(['proxima_dosis' => 'La próxima dosis debe ser posterior a la fecha de aplicación.']);

        $this->assertDatabaseCount('vacunaciones', 0);
    }

    public function test_veterinarian_registers_desparasitacion(): void
    {
        $mascota = $this->createMascota();

        $this->actingAs($this->createUser('Veterinario'))
            ->post(route('desparasitaciones.store', $mascota), [
                'producto' => 'Albendazol',
                'dosis' => '1 tableta',
                'fecha_aplicacion' => '2026-10-20',
                'proxima_aplicacion' => '2027-01-20',
            ])
            ->assertRedirect(route('expedientes.show', $mascota));

        $this->assertDatabaseHas('desparasitaciones', ['producto' => 'Albendazol', 'dosis' => '1 tableta']);
    }

    public function test_receptionist_cannot_register_vacuna_or_desparasitacion(): void
    {
        $mascota = $this->createMascota();
        $this->actingAs($this->createUser('Recepcionista'));

        $this->get(route('vacunaciones.create', $mascota))->assertForbidden();
        $this->post(route('vacunaciones.store', $mascota), [
            'nombre_vacuna' => 'Antirrábica',
            'fecha_aplicacion' => '2026-10-20',
        ])->assertForbidden();

        $this->get(route('desparasitaciones.create', $mascota))->assertForbidden();
        $this->post(route('desparasitaciones.store', $mascota), [
            'producto' => 'Albendazol',
            'dosis' => '1 tableta',
            'fecha_aplicacion' => '2026-10-20',
        ])->assertForbidden();

        $this->assertDatabaseCount('vacunaciones', 0);
        $this->assertDatabaseCount('desparasitaciones', 0);
    }

    /* ------------------------------------------------------------------
     | Editar datos del expediente
     * ------------------------------------------------------------------ */

    public function test_veterinarian_updates_expediente_without_changing_opening_date(): void
    {
        $mascota = $this->createMascota();
        $expediente = $mascota->expediente()->create(['fecha_apertura' => '2026-01-15 09:00:00']);

        $this->actingAs($this->createUser('Veterinario'))
            ->put(route('expedientes.update', $mascota), [
                'alergias' => 'Penicilina',
                'antecedentes' => 'Cirugía de cadera en 2025',
            ])
            ->assertRedirect(route('expedientes.show', $mascota))
            ->assertSessionHas('status', 'Los datos del expediente se actualizaron correctamente.');

        $this->assertDatabaseHas('expedientes', [
            'id' => $expediente->id,
            'alergias' => 'Penicilina',
            'antecedentes' => 'Cirugía de cadera en 2025',
            'fecha_apertura' => '2026-01-15 09:00:00',
        ]);
    }

    public function test_veterinarian_opens_expediente_from_edit_form(): void
    {
        $mascota = $this->createMascota();

        $this->actingAs($this->createUser('Veterinario'))
            ->put(route('expedientes.update', $mascota), ['alergias' => 'Polen'])
            ->assertSessionHas('status', 'El expediente se abrió correctamente.');

        $this->assertDatabaseCount('expedientes', 1);
        $this->assertDatabaseHas('expedientes', ['mascota_id' => $mascota->id, 'alergias' => 'Polen']);
    }

    public function test_receptionist_cannot_edit_expediente(): void
    {
        $mascota = $this->createMascota();
        $mascota->expediente()->create(['fecha_apertura' => now(), 'alergias' => 'Penicilina']);

        $this->actingAs($this->createUser('Recepcionista'));

        $this->get(route('expedientes.edit', $mascota))->assertForbidden();
        $this->put(route('expedientes.update', $mascota), ['alergias' => ''])->assertForbidden();

        $this->assertDatabaseHas('expedientes', ['mascota_id' => $mascota->id, 'alergias' => 'Penicilina']);
    }

    /* ------------------------------------------------------------------
     | Ayudantes
     * ------------------------------------------------------------------ */

    /**
     * Crea un usuario activo con el rol indicado.
     */
    private function createUser(string $rol): User
    {
        $role = Role::query()->firstOrCreate(
            ['nombre' => $rol],
            ['descripcion' => "Rol {$rol} para pruebas."],
        );

        return User::factory()->create(['rol_id' => $role->id]);
    }

    /**
     * Crea una mascota con su propietario.
     */
    private function createMascota(string $nombre = 'Firulais', string $propietario = 'Cliente de prueba'): Mascota
    {
        $cliente = Cliente::query()->create([
            'nombre' => $propietario,
            'telefono' => '70000000',
            'direccion' => 'San Miguel',
        ]);

        return Mascota::query()->create([
            'cliente_id' => $cliente->id,
            'nombre' => $nombre,
            'especie' => 'Canino',
            'sexo' => 'macho',
            'esterilizado' => false,
        ]);
    }
}
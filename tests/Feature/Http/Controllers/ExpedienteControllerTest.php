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
 * Pruebas del módulo de expedientes clínicos: lista, expediente, historial de consultas,
 * edición de datos, consultas (registro y corrección), vacunas y desparasitaciones.
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
     | Historial de consultas
     * ------------------------------------------------------------------ */

    public function test_show_displays_only_the_most_recent_consulta_and_history_button(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();
        $this->createConsulta($mascota, $veterinario, 'Consulta 1', 'Sano', '2026-10-01 09:00:00');
        $this->createConsulta($mascota, $veterinario, 'Consulta 2', 'Sano', '2026-10-05 09:00:00');
        $this->createConsulta($mascota, $veterinario, 'Consulta 3', 'Sano', '2026-10-10 09:00:00');

        $this->actingAs($this->createUser('Recepcionista'))
            ->get(route('expedientes.show', $mascota))
            ->assertOk()
            ->assertSee('Consultas (3)')
            ->assertSee('Se muestra la más reciente.')
            ->assertSee('Ver historial completo')
            ->assertSee('Consulta 3')
            ->assertDontSee('Consulta 2')
            ->assertDontSee('Consulta 1');
    }

    public function test_receptionist_sees_full_history(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();
        $this->createConsulta($mascota, $veterinario, 'Consulta 1', 'Sano', '2026-10-01 09:00:00');
        $this->createConsulta($mascota, $veterinario, 'Consulta 2', 'Sano', '2026-10-05 09:00:00');
        $this->createConsulta($mascota, $veterinario, 'Consulta 3', 'Sano', '2026-10-10 09:00:00');

        $this->actingAs($this->createUser('Recepcionista'))
            ->get(route('expedientes.consultas', $mascota))
            ->assertOk()
            ->assertSee('Consulta 1')
            ->assertSee('Consulta 2')
            ->assertSee('Consulta 3')
            ->assertDontSee('Corregir');
    }

    public function test_history_filters_by_motivo_or_diagnostico(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();
        $this->createConsulta($mascota, $veterinario, 'Vacunación anual', 'Apto para vacunar', '2026-10-01 09:00:00');
        $this->createConsulta($mascota, $veterinario, 'Rascado de oreja', 'Otitis externa', '2026-10-05 09:00:00');

        $this->actingAs($veterinario);

        // Por diagnóstico
        $this->get(route('expedientes.consultas', ['mascota' => $mascota, 'buscar' => 'otitis']))
            ->assertOk()
            ->assertSee('Rascado de oreja')
            ->assertDontSee('Vacunación anual');

        // Por motivo
        $this->get(route('expedientes.consultas', ['mascota' => $mascota, 'buscar' => 'vacunación']))
            ->assertOk()
            ->assertSee('Vacunación anual')
            ->assertDontSee('Rascado de oreja');
    }

    public function test_history_paginates_ten_per_page(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();

        // Números con dos cifras para que "Control número 02" no esté dentro de "Control número 12"
        foreach (range(1, 12) as $numero) {
            $this->createConsulta(
                $mascota,
                $veterinario,
                sprintf('Control número %02d', $numero),
                'Sano',
                CarbonImmutable::parse('2026-09-01 09:00:00')->addDays($numero)->toDateTimeString(),
            );
        }

        $this->actingAs($veterinario);

        // Primera página: de la 12 a la 03
        $this->get(route('expedientes.consultas', $mascota))
            ->assertOk()
            ->assertSee('Mostrando 1–10 de 12 consultas')
            ->assertSee('Control número 12')
            ->assertSee('Control número 03')
            ->assertDontSee('Control número 02')
            ->assertDontSee('Control número 01');

        // Segunda página: la 02 y la 01
        $this->get(route('expedientes.consultas', ['mascota' => $mascota, 'page' => 2]))
            ->assertOk()
            ->assertSee('Mostrando 11–12 de 12 consultas')
            ->assertSee('Control número 02')
            ->assertSee('Control número 01')
            ->assertDontSee('Control número 03');
    }

    public function test_history_without_expediente_shows_message(): void
    {
        $mascota = $this->createMascota();

        $this->actingAs($this->createUser('Recepcionista'))
            ->get(route('expedientes.consultas', $mascota))
            ->assertOk()
            ->assertSee('todavía no tiene expediente clínico ni consultas registradas');
    }

    public function test_history_requires_authentication(): void
    {
        $mascota = $this->createMascota();

        $this->get(route('expedientes.consultas', $mascota))->assertRedirect(route('login'));
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
     | Corregir consultas
     * ------------------------------------------------------------------ */

    public function test_author_corrects_consulta_the_same_day_and_replaces_medications(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();
        $consulta = $this->createConsulta($mascota, $veterinario);
        $consulta->tratamientos()->create([
            'medicamento' => 'Amoxicilina',
            'dosis' => '1 tableta',
            'frecuencia' => 'cada 8 horas',
            'duracion' => '7 días',
        ]);

        // Dos horas después, el mismo día
        $this->travel(2)->hours();

        $this->actingAs($veterinario)
            ->put(route('consultas.update', [$mascota, $consulta]), [
                'motivo' => 'Control',
                'diagnostico' => 'Otitis externa',
                'tratamientos' => [
                    ['medicamento' => 'Gotas óticas', 'dosis' => '3 gotas', 'frecuencia' => 'cada 12 horas', 'duracion' => '10 días'],
                ],
            ])
            ->assertRedirect(route('expedientes.show', $mascota))
            ->assertSessionHas('status', 'La consulta se corrigió correctamente.');

        $consulta->refresh();
        $this->assertSame('Otitis externa', $consulta->diagnostico);
        $this->assertTrue($consulta->fueCorregida());
        $this->assertDatabaseCount('tratamientos', 1);
        $this->assertDatabaseHas('tratamientos', ['consulta_id' => $consulta->id, 'medicamento' => 'Gotas óticas']);
        $this->assertDatabaseMissing('tratamientos', ['medicamento' => 'Amoxicilina']);
    }

    public function test_other_veterinarian_cannot_correct_consulta(): void
    {
        $mascota = $this->createMascota();
        $consulta = $this->createConsulta($mascota, $this->createUser('Veterinario'));

        $this->actingAs($this->createUser('Veterinario'));

        $this->get(route('consultas.edit', [$mascota, $consulta]))->assertForbidden();
        $this->put(route('consultas.update', [$mascota, $consulta]), ['motivo' => 'X', 'diagnostico' => 'Y'])
            ->assertForbidden();

        $this->assertSame('Sano', $consulta->fresh()->diagnostico);
    }

    public function test_consulta_cannot_be_corrected_the_next_day(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();
        $consulta = $this->createConsulta($mascota, $veterinario);

        // Al día siguiente la consulta queda cerrada
        $this->travel(1)->days();

        $this->actingAs($veterinario)
            ->put(route('consultas.update', [$mascota, $consulta]), ['motivo' => 'X', 'diagnostico' => 'Y'])
            ->assertForbidden();

        $this->assertSame('Sano', $consulta->fresh()->diagnostico);
    }

    public function test_consulta_from_another_pet_returns_not_found(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $consulta = $this->createConsulta($this->createMascota('Firulais'), $veterinario);
        $otraMascota = $this->createMascota('Michi');

        $this->actingAs($veterinario)
            ->get(route('consultas.edit', [$otraMascota, $consulta]))
            ->assertNotFound();
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

    /**
     * Crea una consulta registrada en este momento por el veterinario indicado.
     * El motivo, el diagnóstico y la fecha de la consulta se pueden personalizar.
     */
    private function createConsulta(
        Mascota $mascota,
        User $veterinario,
        string $motivo = 'Control',
        string $diagnostico = 'Sano',
        ?string $fechaConsulta = null,
    ): Consulta {
        $expediente = $mascota->expediente()->firstOrCreate([], ['fecha_apertura' => now()]);

        return Consulta::query()->create([
            'expediente_id' => $expediente->id,
            'veterinario_id' => $veterinario->id,
            'fecha_consulta' => $fechaConsulta ?? now(),
            'motivo' => $motivo,
            'diagnostico' => $diagnostico,
        ]);
    }
}
<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Consulta;
use App\Models\Expediente;
use App\Models\Mascota;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del módulo de reportes: citas, consultas por veterinario,
 * diagnósticos frecuentes y próximas dosis.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class ReporteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Hoy es 20 de octubre: el rango por defecto va del 1 al 20 de octubre
        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00'));
    }

    public function test_index_shows_reports_according_to_role(): void
    {
        $this->actingAs($this->createUser('Administrador'))
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Citas')
            ->assertSee('Consultas por veterinario')
            ->assertSee('Diagnósticos más frecuentes')
            ->assertSee('Próximas dosis');

        $this->actingAs($this->createUser('Recepcionista'))
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Próximas dosis')
            ->assertDontSee('Consultas por veterinario')
            ->assertDontSee('Diagnósticos más frecuentes');
    }

    public function test_citas_report_counts_by_status_and_attendance(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();

        foreach (['atendida', 'atendida', 'atendida', 'no_asistio', 'cancelada', 'pendiente'] as $dia => $estado) {
            $this->createCita($mascota, $veterinario, '2026-10-' . (10 + $dia) . ' 09:00:00', $estado);
        }
        // Fuera del período: no debe contarse
        $this->createCita($mascota, $veterinario, '2026-09-15 09:00:00', 'atendida');

        $this->actingAs($this->createUser('Administrador'))
            ->get(route('reportes.citas'))
            ->assertOk()
            ->assertViewHas('total', 6)
            ->assertViewHas('totales', fn ($totales): bool => $totales['atendida'] === 3
                && $totales['no_asistio'] === 1
                && $totales['cancelada'] === 1
                && $totales['pendiente'] === 1)
            // 3 atendidas de 4 que debieron ocurrir (3 atendidas + 1 no asistió)
            ->assertViewHas('asistencia', 75);
    }

    public function test_citas_report_groups_by_veterinarian(): void
    {
        $mascota = $this->createMascota();
        $doctorA = $this->createUser('Veterinario', 'Dr. Alfa');
        $doctorB = $this->createUser('Veterinario', 'Dra. Beta');

        $this->createCita($mascota, $doctorA, '2026-10-05 09:00:00', 'atendida');
        $this->createCita($mascota, $doctorA, '2026-10-06 09:00:00', 'cancelada');
        $this->createCita($mascota, $doctorB, '2026-10-07 09:00:00', 'atendida');

        $this->actingAs($this->createUser('Administrador'))
            ->get(route('reportes.citas'))
            ->assertOk()
            ->assertViewHas('porVeterinario', function ($filas): bool {
                $alfa = $filas->firstWhere('nombre', 'Dr. Alfa');
                $beta = $filas->firstWhere('nombre', 'Dra. Beta');

                return $alfa['total'] === 2 && $alfa['estados']['cancelada'] === 1
                    && $beta['total'] === 1 && $beta['estados']['atendida'] === 1;
            });
    }

    public function test_consultas_report_counts_and_percentages_by_veterinarian(): void
    {
        $expediente = $this->createExpediente();
        $doctorA = $this->createUser('Veterinario', 'Dr. Alfa');
        $doctorB = $this->createUser('Veterinario', 'Dra. Beta');

        foreach (range(1, 3) as $dia) {
            $this->createConsulta($expediente, $doctorA, "2026-10-0{$dia} 09:00:00");
        }
        $this->createConsulta($expediente, $doctorB, '2026-10-04 09:00:00');

        $this->actingAs($this->createUser('Administrador'))
            ->get(route('reportes.consultas'))
            ->assertOk()
            ->assertViewHas('total', 4)
            ->assertViewHas('porVeterinario', fn ($filas): bool => $filas[0]['nombre'] === 'Dr. Alfa'
                && $filas[0]['total'] === 3
                && (int) $filas[0]['porcentaje'] === 75
                && $filas[1]['nombre'] === 'Dra. Beta'
                && (int) $filas[1]['porcentaje'] === 25);
    }

    public function test_diagnosticos_report_groups_ignoring_case_and_spaces(): void
    {
        $expediente = $this->createExpediente();
        $veterinario = $this->createUser('Veterinario');

        foreach (['Otitis', 'otitis ', 'OTITIS', 'Gastritis'] as $dia => $diagnostico) {
            $this->createConsulta($expediente, $veterinario, '2026-10-1' . $dia . ' 09:00:00', $diagnostico);
        }

        $this->actingAs($veterinario)
            ->get(route('reportes.diagnosticos'))
            ->assertOk()
            ->assertViewHas('total', 4)
            ->assertViewHas('diagnosticos', fn ($diagnosticos): bool => $diagnosticos->count() === 2
                && $diagnosticos[0]['diagnostico'] === 'Otitis'
                && $diagnosticos[0]['total'] === 3);
    }

    public function test_dosis_report_shows_upcoming_and_overdue_using_latest_application(): void
    {
        $expediente = $this->createExpediente();

        // Antirrábica: la de 2025 "venció", pero ya se volvió a aplicar en octubre 2026
        $expediente->vacunaciones()->create(['nombre_vacuna' => 'Antirrábica', 'fecha_aplicacion' => '2025-10-01', 'proxima_dosis' => '2026-10-01']);
        $expediente->vacunaciones()->create(['nombre_vacuna' => 'Antirrábica', 'fecha_aplicacion' => '2026-10-15', 'proxima_dosis' => '2027-10-15']);

        // Por vencer en 5 días
        $expediente->vacunaciones()->create(['nombre_vacuna' => 'Parvovirus', 'fecha_aplicacion' => '2026-09-25', 'proxima_dosis' => '2026-10-25']);

        // Vencida hace 10 días
        $expediente->desparasitaciones()->create(['producto' => 'Albendazol', 'dosis' => '1 tableta', 'fecha_aplicacion' => '2026-07-10', 'proxima_aplicacion' => '2026-10-10']);

        $this->actingAs($this->createUser('Recepcionista'));

        $this->get(route('reportes.dosis'))
            ->assertOk()
            ->assertViewHas('filas', fn ($filas): bool => $filas->count() === 1
                && $filas[0]['nombre'] === 'Parvovirus');

        $this->get(route('reportes.dosis', ['estado' => 'vencidas']))
            ->assertOk()
            ->assertViewHas('filas', fn ($filas): bool => $filas->count() === 1
                && $filas[0]['nombre'] === 'Albendazol');
    }

    public function test_receptionist_only_accesses_the_doses_report(): void
    {
        $this->actingAs($this->createUser('Recepcionista'));

        $this->get(route('reportes.citas'))->assertForbidden();
        $this->get(route('reportes.consultas'))->assertForbidden();
        $this->get(route('reportes.diagnosticos'))->assertForbidden();
        $this->get(route('reportes.dosis'))->assertOk();
    }

    public function test_reports_require_authentication(): void
    {
        $this->get(route('reportes.index'))->assertRedirect(route('login'));
        $this->get(route('reportes.dosis'))->assertRedirect(route('login'));
    }

    public function test_date_filter_rejects_an_inverted_range(): void
    {
        $this->actingAs($this->createUser('Administrador'))
            ->get(route('reportes.citas', ['desde' => '2026-10-20', 'hasta' => '2026-10-10']))
            ->assertSessionHasErrors(['hasta' => 'La fecha final debe ser igual o posterior a la fecha inicial.']);
    }

    /* ------------------------------------------------------------------
     | Ayudantes
     * ------------------------------------------------------------------ */

    private function createUser(string $rol, ?string $nombre = null): User
    {
        $role = Role::query()->firstOrCreate(
            ['nombre' => $rol],
            ['descripcion' => "Rol {$rol} para pruebas."],
        );

        return User::factory()->create(array_filter([
            'rol_id' => $role->id,
            'nombre' => $nombre,
        ]));
    }

    private function createMascota(): Mascota
    {
        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente de prueba',
            'telefono' => '70000000',
            'direccion' => 'San Miguel',
        ]);

        return Mascota::query()->create([
            'cliente_id' => $cliente->id,
            'nombre' => 'Firulais',
            'especie' => 'Canino',
            'sexo' => 'macho',
            'esterilizado' => false,
        ]);
    }

    private function createExpediente(): Expediente
    {
        return $this->createMascota()->expediente()->create(['fecha_apertura' => now()]);
    }

    private function createCita(Mascota $mascota, User $veterinario, string $fecha, string $estado): Cita
    {
        return Cita::query()->create([
            'mascota_id' => $mascota->id,
            'veterinario_id' => $veterinario->id,
            'fecha_cita' => $fecha,
            'estado' => $estado,
            'motivo' => 'Control',
        ]);
    }

    private function createConsulta(Expediente $expediente, User $veterinario, string $fecha, string $diagnostico = 'Sano'): Consulta
    {
        return Consulta::query()->create([
            'expediente_id' => $expediente->id,
            'veterinario_id' => $veterinario->id,
            'fecha_consulta' => $fecha,
            'motivo' => 'Control',
            'diagnostico' => $diagnostico,
        ]);
    }
}
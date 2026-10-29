<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Mascota;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de la página de inicio con los indicadores del día.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00'));
    }

    public function test_dashboard_counts_todays_appointments_without_cancelled(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();

        $this->createCita($mascota, $veterinario, '2026-10-20 11:00:00', 'pendiente');
        $this->createCita($mascota, $veterinario, '2026-10-20 09:00:00', 'atendida');
        $this->createCita($mascota, $veterinario, '2026-10-20 14:00:00', 'cancelada');
        $this->createCita($mascota, $veterinario, '2026-10-21 09:00:00', 'pendiente'); // mañana

        $this->actingAs($this->createUser('Recepcionista'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('citasHoy', 2)
            ->assertViewHas('citasPendientesHoy', 1)
            ->assertViewHas('proximasCitas', fn ($citas): bool => $citas->count() === 1);
    }

    public function test_veterinarian_only_sees_own_appointments(): void
    {
        $doctorA = $this->createUser('Veterinario');
        $doctorB = $this->createUser('Veterinario');
        $mascota = $this->createMascota();

        $this->createCita($mascota, $doctorA, '2026-10-20 11:00:00', 'pendiente');
        $this->createCita($mascota, $doctorB, '2026-10-20 12:00:00', 'pendiente');

        $this->actingAs($doctorA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tus citas de hoy')
            ->assertViewHas('citasHoy', 1);
    }

    public function test_dashboard_shows_todays_sales_and_low_stock(): void
    {
        $administrador = $this->createUser('Administrador');

        Venta::query()->create(['folio' => 'V-1', 'usuario_id' => $administrador->id, 'metodo_pago' => 'efectivo', 'fecha_venta' => '2026-10-20 08:30:00', 'total' => 25.50]);
        Venta::query()->create(['folio' => 'V-2', 'usuario_id' => $administrador->id, 'metodo_pago' => 'efectivo', 'fecha_venta' => '2026-10-20 09:15:00', 'total' => 10.00]);
        Venta::query()->create(['folio' => 'V-3', 'usuario_id' => $administrador->id, 'metodo_pago' => 'efectivo', 'fecha_venta' => '2026-10-19 16:00:00', 'total' => 99.00]); // ayer

        $categoria = CategoriaProducto::query()->firstOrCreate(['nombre' => 'Medicamentos']);
        Producto::query()->create(['categoria_id' => $categoria->id, 'codigo' => 'BAJO', 'nombre' => 'Bajo', 'unidad' => 'caja', 'precio_compra' => 1, 'precio_venta' => 2, 'stock' => 1, 'stock_minimo' => 5, 'activo' => true]);
        Producto::query()->create(['categoria_id' => $categoria->id, 'codigo' => 'OK', 'nombre' => 'Normal', 'unidad' => 'caja', 'precio_compra' => 1, 'precio_venta' => 2, 'stock' => 20, 'stock_minimo' => 5, 'activo' => true]);

        $this->actingAs($administrador)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('ventasHoyTotal', 35.5)
            ->assertViewHas('ventasHoyCantidad', 2)
            ->assertViewHas('stockBajo', 1)
            ->assertSee('$ 35.50');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    private function createUser(string $rol): User
    {
        $role = Role::query()->firstOrCreate(['nombre' => $rol], ['descripcion' => "Rol {$rol} para pruebas."]);

        return User::factory()->create(['rol_id' => $role->id]);
    }

    private function createMascota(): Mascota
    {
        $cliente = Cliente::query()->create(['nombre' => 'Cliente de prueba', 'telefono' => '70000000', 'direccion' => 'San Miguel']);

        return Mascota::query()->create([
            'cliente_id' => $cliente->id,
            'nombre' => 'Firulais',
            'especie' => 'Canino',
            'sexo' => 'macho',
            'esterilizado' => false,
        ]);
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
}
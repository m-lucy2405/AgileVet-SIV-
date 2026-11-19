<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\CategoriaProducto;
use App\Models\LoteProducto;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Services\InventarioService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de la fecha de caducidad por lote: al crear el producto y al corregirla.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class LoteCaducidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00'));
    }

    public function test_product_with_initial_lot_near_expiration_is_marked(): void
    {
        $categoria = CategoriaProducto::query()->firstOrCreate(['nombre' => 'Medicamentos']);

        $this->actingAs($this->createUser('Administrador'))
            ->post(route('productos.store'), [
                ...$this->datosProducto($categoria),
                'stock_inicial' => 5,
                'codigo_lote_inicial' => 'L-001',
                'fecha_caducidad_inicial' => '2026-10-30',
            ])
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('lotes_producto', ['codigo_lote' => 'L-001', 'cantidad_disponible' => 5]);
        $this->assertSame('2026-10-30', LoteProducto::query()->first()->fecha_caducidad->toDateString());

        $this->get(route('productos.index', ['alerta' => 'por_vencer']))
            ->assertViewHas('productos', fn ($productos): bool => $productos->total() === 1)
            ->assertViewHas('resumen', fn ($resumen): bool => $resumen['por_vencer'] === 1);
    }

    public function test_expiration_date_without_stock_is_rejected(): void
    {
        $categoria = CategoriaProducto::query()->firstOrCreate(['nombre' => 'Medicamentos']);

        $this->actingAs($this->createUser('Administrador'))
            ->post(route('productos.store'), [
                ...$this->datosProducto($categoria),
                'stock_inicial' => 0,
                'fecha_caducidad_inicial' => '2026-12-01',
            ])
            ->assertSessionHasErrors(['fecha_caducidad_inicial' => 'Para registrar la fecha de caducidad, indica también el stock inicial.']);

        $this->assertSame(0, Producto::query()->count());
    }

    public function test_expired_initial_lot_is_rejected(): void
    {
        $categoria = CategoriaProducto::query()->firstOrCreate(['nombre' => 'Medicamentos']);

        $this->actingAs($this->createUser('Administrador'))
            ->post(route('productos.store'), [
                ...$this->datosProducto($categoria),
                'stock_inicial' => 5,
                'fecha_caducidad_inicial' => '2026-10-01',
            ])
            ->assertSessionHasErrors(['fecha_caducidad_inicial' => 'No se puede registrar un lote ya vencido.']);

        $this->assertSame(0, Producto::query()->count());
    }

    public function test_administrator_corrects_lot_expiration_and_it_is_audited(): void
    {
        $administrador = $this->createUser('Administrador');
        $lote = $this->createProductoConLote($administrador, '2027-06-01');

        $this->actingAs($administrador);

        // Con caducidad lejana, no está por vencer
        $this->get(route('productos.index', ['alerta' => 'por_vencer']))
            ->assertViewHas('productos', fn ($productos): bool => $productos->total() === 0);

        $this->patch(route('lotes.caducidad', $lote), ['fecha_caducidad' => '2026-10-25'])
            ->assertRedirect(route('productos.edit', $lote->producto_id))
            ->assertSessionHas('status');

        $this->assertSame('2026-10-25', $lote->fresh()->fecha_caducidad->toDateString());

        // Ahora sí aparece como por vencer
        $this->get(route('productos.index', ['alerta' => 'por_vencer']))
            ->assertViewHas('productos', fn ($productos): bool => $productos->total() === 1);

        $registro = Auditoria::query()->where('tabla_afectada', 'lotes_producto')->latest('id')->firstOrFail();
        $this->assertSame(['fecha_caducidad' => '2027-06-01'], $registro->valores_anteriores);
        $this->assertSame(['fecha_caducidad' => '2026-10-25'], $registro->valores_nuevos);
    }

    public function test_receptionist_cannot_correct_lot_expiration(): void
    {
        $lote = $this->createProductoConLote($this->createUser('Administrador'), '2027-06-01');

        $this->actingAs($this->createUser('Recepcionista'))
            ->patch(route('lotes.caducidad', $lote), ['fecha_caducidad' => '2026-10-25'])
            ->assertForbidden();

        $this->assertSame('2027-06-01', $lote->fresh()->fecha_caducidad->toDateString());
    }

    private function createUser(string $rol): User
    {
        $role = Role::query()->firstOrCreate(['nombre' => $rol], ['descripcion' => "Rol {$rol} para pruebas."]);

        return User::factory()->create(['rol_id' => $role->id]);
    }

    private function datosProducto(CategoriaProducto $categoria): array
    {
        return [
            'categoria_id' => $categoria->id,
            'codigo' => 'MED-100',
            'nombre' => 'Amoxicilina 250 mg',
            'unidad' => 'caja',
            'precio_compra' => 4.5,
            'precio_venta' => 7.25,
            'stock_minimo' => 1,
        ];
    }

    private function createProductoConLote(User $usuario, string $fechaCaducidad): LoteProducto
    {
        $categoria = CategoriaProducto::query()->firstOrCreate(['nombre' => 'Medicamentos']);
        $producto = Producto::query()->create([
            ...$this->datosProducto($categoria),
            'stock' => 0,
            'activo' => true,
        ]);

        app(InventarioService::class)->registrar(
            $producto, MovimientoInventario::ENTRADA, 10, 'Compra a proveedor', $usuario, 'L-500', $fechaCaducidad, '4.50',
        );

        return LoteProducto::query()->where('codigo_lote', 'L-500')->firstOrFail();
    }
}
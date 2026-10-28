<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Services\InventarioService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Pruebas del módulo de inventario: servicio de stock, catálogo, kárdex y permisos.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class InventarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00'));
    }

    /* ------------------------------------------------------------------
     | Servicio de inventario
     * ------------------------------------------------------------------ */

    public function test_entrada_adds_stock_and_records_the_movement(): void
    {
        $producto = $this->createProducto(['stock' => 3]);
        $usuario = $this->createUser('Administrador');

        $movimiento = app(InventarioService::class)
            ->registrar($producto, MovimientoInventario::ENTRADA, 20, 'Compra a proveedor', $usuario);

        $this->assertSame(23, $producto->fresh()->stock);
        $this->assertSame(23, $producto->stock); // también se actualiza el objeto recibido
        $this->assertSame(3, $movimiento->stock_anterior);
        $this->assertSame(23, $movimiento->stock_nuevo);
        $this->assertSame(20, $movimiento->cantidad);
        $this->assertSame($usuario->id, $movimiento->usuario_id);
    }

    public function test_salida_that_exceeds_stock_is_rejected_without_changing_anything(): void
    {
        $producto = $this->createProducto(['stock' => 5]);
        $usuario = $this->createUser('Administrador');
        $servicio = app(InventarioService::class);

        $servicio->registrar($producto, MovimientoInventario::SALIDA, 2, 'Uso en consulta', $usuario);
        $this->assertSame(3, $producto->fresh()->stock);

        $this->assertThrows(
            fn () => $servicio->registrar($producto, MovimientoInventario::SALIDA, 10, 'Uso en consulta', $usuario),
            ValidationException::class,
        );

        $this->assertSame(3, $producto->fresh()->stock);
        $this->assertSame(1, MovimientoInventario::query()->count());
    }

    public function test_ajuste_sets_counted_stock_and_rejects_no_change(): void
    {
        $producto = $this->createProducto(['stock' => 10]);
        $usuario = $this->createUser('Administrador');
        $servicio = app(InventarioService::class);

        $movimiento = $servicio->registrar($producto, MovimientoInventario::AJUSTE, 7, 'Conteo físico de inventario', $usuario);

        $this->assertSame(7, $producto->fresh()->stock);
        $this->assertSame(3, $movimiento->cantidad); // la diferencia entre 10 y 7

        $this->assertThrows(
            fn () => $servicio->registrar($producto, MovimientoInventario::AJUSTE, 7, 'Conteo físico de inventario', $usuario),
            ValidationException::class,
        );
    }

    /* ------------------------------------------------------------------
     | Catálogo
     * ------------------------------------------------------------------ */

    public function test_administrator_creates_product_with_initial_stock_as_first_entry(): void
    {
        $categoria = $this->createCategoria();

        $this->actingAs($this->createUser('Administrador'))
            ->post(route('productos.store'), $this->datosProducto($categoria, ['stock_inicial' => 15]))
            ->assertRedirect(route('productos.index'))
            ->assertSessionHas('status', 'El producto se registró correctamente.');

        $producto = Producto::query()->where('codigo', 'MED-100')->firstOrFail();

        $this->assertSame(15, $producto->stock);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $producto->id,
            'tipo' => MovimientoInventario::ENTRADA,
            'cantidad' => 15,
            'stock_anterior' => 0,
            'stock_nuevo' => 15,
            'motivo' => 'Inventario inicial',
        ]);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $categoria = $this->createCategoria();
        $this->createProducto(['codigo' => 'MED-100', 'categoria_id' => $categoria->id]);

        $this->actingAs($this->createUser('Administrador'))
            ->post(route('productos.store'), $this->datosProducto($categoria))
            ->assertSessionHasErrors(['codigo' => 'Ya existe un producto con ese código.']);

        $this->assertSame(1, Producto::query()->count());
    }

    public function test_editing_a_product_never_changes_its_stock(): void
    {
        $producto = $this->createProducto(['stock' => 8, 'precio_venta' => 5]);

        $this->actingAs($this->createUser('Administrador'))
            ->put(route('productos.update', $producto), [
                ...$this->datosProducto($producto->categoria, ['codigo' => $producto->codigo, 'precio_venta' => 9.5]),
                'stock' => 999,
            ])
            ->assertRedirect(route('productos.index'));

        $producto->refresh();
        $this->assertSame(8, $producto->stock);
        $this->assertSame('9.50', $producto->precio_venta);
    }

    public function test_deactivated_product_is_hidden_and_does_not_accept_movements(): void
    {
        $producto = $this->createProducto(['stock' => 5]);
        $this->actingAs($this->createUser('Administrador'));

        $this->patch(route('productos.estado', $producto))->assertRedirect();
        $this->assertFalse($producto->fresh()->activo);

        $this->get(route('productos.index'))
            ->assertViewHas('productos', fn ($productos): bool => $productos->total() === 0);

        $this->post(route('movimientos.store', $producto), [
            'tipo' => MovimientoInventario::ENTRADA,
            'cantidad' => 5,
            'motivo' => 'Compra a proveedor',
        ])->assertSessionHasErrors('tipo');

        $this->assertSame(5, $producto->fresh()->stock);
    }

    public function test_catalog_filters_by_low_stock_and_expiration(): void
    {
        $categoria = $this->createCategoria();
        $this->createProducto(['codigo' => 'BAJO', 'nombre' => 'A bajo', 'stock' => 2, 'stock_minimo' => 5, 'categoria_id' => $categoria->id]);
        $this->createProducto(['codigo' => 'VENCE', 'nombre' => 'B vence', 'stock' => 50, 'fecha_vencimiento' => '2026-11-01', 'categoria_id' => $categoria->id]);
        $this->createProducto(['codigo' => 'NORMAL', 'nombre' => 'C normal', 'stock' => 50, 'fecha_vencimiento' => '2027-12-01', 'categoria_id' => $categoria->id]);

        $this->actingAs($this->createUser('Recepcionista'));

        $codigos = fn ($productos): array => collect($productos->items())->pluck('codigo')->all();

        $this->get(route('productos.index', ['alerta' => 'stock_bajo']))
            ->assertViewHas('productos', fn ($productos): bool => $codigos($productos) === ['BAJO']);

        $this->get(route('productos.index', ['alerta' => 'por_vencer']))
            ->assertViewHas('productos', fn ($productos): bool => $codigos($productos) === ['VENCE']);

        $this->get(route('productos.index'))
            ->assertViewHas('resumen', fn ($resumen): bool => $resumen['activos'] === 3
                && $resumen['stock_bajo'] === 1
                && $resumen['por_vencer'] === 1);
    }

    /* ------------------------------------------------------------------
     | Movimientos y kárdex
     * ------------------------------------------------------------------ */

    public function test_salida_without_enough_stock_shows_error_in_the_form(): void
    {
        $producto = $this->createProducto(['stock' => 4, 'unidad' => 'caja']);

        $this->actingAs($this->createUser('Administrador'))
            ->post(route('movimientos.store', $producto), [
                'tipo' => MovimientoInventario::SALIDA,
                'cantidad' => 10,
                'motivo' => 'Uso en consulta',
            ])
            ->assertSessionHasErrors(['cantidad' => 'No hay suficiente stock. Disponible: 4 caja.']);

        $this->assertSame(4, $producto->fresh()->stock);
    }

    public function test_kardex_shows_movements_totals_and_filters_by_type(): void
    {
        $producto = $this->createProducto(['stock' => 0]);
        $administrador = $this->createUser('Administrador');
        $servicio = app(InventarioService::class);

        $servicio->registrar($producto, MovimientoInventario::ENTRADA, 20, 'Compra a proveedor', $administrador);
        $servicio->registrar($producto, MovimientoInventario::SALIDA, 5, 'Uso en consulta', $administrador);
        $servicio->registrar($producto, MovimientoInventario::AJUSTE, 12, 'Conteo físico de inventario', $administrador);

        $this->actingAs($this->createUser('Recepcionista'));

        // 20 entraron; salieron 5 (salida) + 3 (ajuste de 15 a 12)
        $this->get(route('productos.show', $producto))
            ->assertOk()
            ->assertSee('Compra a proveedor')
            ->assertViewHas('totales', fn ($totales): bool => $totales['cantidad'] === 3
                && $totales['entradas'] === 20
                && $totales['salidas'] === 8);

        $this->get(route('productos.show', ['producto' => $producto, 'tipo' => MovimientoInventario::AJUSTE]))
            ->assertOk()
            ->assertViewHas('movimientos', fn ($movimientos): bool => $movimientos->total() === 1
                && $movimientos->first()->tipo === MovimientoInventario::AJUSTE);
    }

    /* ------------------------------------------------------------------
     | Permisos
     * ------------------------------------------------------------------ */

    public function test_receptionist_can_view_but_not_manage_inventory(): void
    {
        $producto = $this->createProducto();
        $this->actingAs($this->createUser('Recepcionista'));

        $this->get(route('productos.index'))->assertOk()->assertDontSee('Nuevo producto');
        $this->get(route('productos.show', $producto))->assertOk()->assertDontSee('Registrar movimiento');

        $this->get(route('productos.create'))->assertForbidden();
        $this->post(route('productos.store'), $this->datosProducto($producto->categoria, ['codigo' => 'OTRO']))->assertForbidden();
        $this->get(route('productos.edit', $producto))->assertForbidden();
        $this->patch(route('productos.estado', $producto))->assertForbidden();
        $this->get(route('movimientos.create', $producto))->assertForbidden();
        $this->post(route('movimientos.store', $producto), [
            'tipo' => MovimientoInventario::ENTRADA,
            'cantidad' => 5,
            'motivo' => 'Compra',
        ])->assertForbidden();

        $this->assertSame(0, MovimientoInventario::query()->count());
    }

    public function test_create_page_is_not_confused_with_the_kardex(): void
    {
        $this->createCategoria();

        // Sin whereNumber('producto'), "crear" se tomaría como el id de un producto (404)
        $this->actingAs($this->createUser('Administrador'))
            ->get(route('productos.create'))
            ->assertOk()
            ->assertSee('Guardar producto');
    }

    public function test_inventory_requires_authentication(): void
    {
        $producto = $this->createProducto();

        $this->get(route('productos.index'))->assertRedirect(route('login'));
        $this->get(route('productos.show', $producto))->assertRedirect(route('login'));
    }

    /* ------------------------------------------------------------------
     | Ayudantes
     * ------------------------------------------------------------------ */

    private function createUser(string $rol): User
    {
        $role = Role::query()->firstOrCreate(
            ['nombre' => $rol],
            ['descripcion' => "Rol {$rol} para pruebas."],
        );

        return User::factory()->create(['rol_id' => $role->id]);
    }

    private function createCategoria(): CategoriaProducto
    {
        return CategoriaProducto::query()->firstOrCreate(['nombre' => 'Medicamentos']);
    }

    /**
     * Crea un producto directamente en la base (sin pasar por el servicio).
     *
     * @param  array<string, mixed>  $datos  Datos que reemplazan a los predeterminados.
     */
    private function createProducto(array $datos = []): Producto
    {
        $producto = Producto::query()->create([
            'categoria_id' => $datos['categoria_id'] ?? $this->createCategoria()->id,
            'codigo' => 'MED-'.fake()->unique()->numerify('####'),
            'nombre' => 'Producto de prueba',
            'unidad' => 'unidad',
            'precio_compra' => 2,
            'precio_venta' => 3,
            'stock' => 0,
            'stock_minimo' => 0,
            'activo' => true,
            ...$datos,
        ]);

        if ($producto->stock > 0) {
            $producto->lotes()->create([
                'codigo_lote' => 'TEST-'.$producto->id,
                'fecha_caducidad' => $producto->fecha_vencimiento?->toDateString(),
                'cantidad_disponible' => $producto->stock,
                'costo_compra' => $producto->precio_compra,
            ]);
        }

        return $producto;
    }

    /**
     * Datos válidos para el formulario de producto.
     *
     * @param  array<string, mixed>  $datos  Datos que reemplazan a los predeterminados.
     * @return array<string, mixed>
     */
    private function datosProducto(CategoriaProducto $categoria, array $datos = []): array
    {
        return [
            'categoria_id' => $categoria->id,
            'codigo' => 'MED-100',
            'nombre' => 'Amoxicilina 250 mg',
            'unidad' => 'caja',
            'precio_compra' => 4.5,
            'precio_venta' => 7.25,
            'stock_minimo' => 5,
            ...$datos,
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\CategoriaProducto;
use App\Models\LoteProducto;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica autorización, FEFO, integridad de stock, comprobantes y auditoría de ventas.
 *
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class VentaControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fija una fecha estable para las reglas de caducidad de los lotes.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-27 10:00:00'));
    }

    /**
     * La recepcionista vende usando FEFO, omite lotes vencidos y obtiene un ticket auditable.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_receptionist_sale_uses_fefo_skips_expired_lots_and_renders_historical_receipt(): void
    {
        $recepcionista = $this->createUser('Recepcionista');
        $producto = $this->createProducto([
            ['codigo_lote' => 'VENCIDO', 'fecha_caducidad' => '2026-10-26', 'cantidad_disponible' => 2],
            ['codigo_lote' => 'PROXIMO', 'fecha_caducidad' => '2026-11-01', 'cantidad_disponible' => 2],
            ['codigo_lote' => 'LEJANO', 'fecha_caducidad' => '2027-01-01', 'cantidad_disponible' => 4],
            ['codigo_lote' => 'SIN-FECHA', 'fecha_caducidad' => null, 'cantidad_disponible' => 1],
        ], '2.35');

        $response = $this->actingAs($recepcionista)->post(route('ventas.store'), [
            'metodo_pago' => Venta::EFECTIVO,
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 5],
            ],
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'La venta se registró correctamente.')
            ->assertRedirect();
        $this->assertDatabaseCount('ventas', 1);
        $venta = Venta::query()->firstOrFail();
        $response->assertRedirect(route('ventas.comprobante', $venta));
        $this->assertSame('11.75', $venta->total);
        $this->assertSame($recepcionista->id, $venta->usuario_id);
        $this->assertSame('2026-10-27', $venta->fecha_venta->toDateString());
        $this->assertMatchesRegularExpression('/^SIV-20261027-[0-9A-HJKMNP-TV-Z]{26}$/', $venta->folio);
        $this->assertSame(4, $producto->fresh()->stock);

        $this->assertDatabaseHas('lotes_producto', [
            'codigo_lote' => 'VENCIDO',
            'cantidad_disponible' => 2,
        ]);
        $this->assertDatabaseHas('lotes_producto', [
            'codigo_lote' => 'PROXIMO',
            'cantidad_disponible' => 0,
        ]);
        $this->assertDatabaseHas('lotes_producto', [
            'codigo_lote' => 'LEJANO',
            'cantidad_disponible' => 1,
        ]);
        $this->assertDatabaseHas('lotes_producto', [
            'codigo_lote' => 'SIN-FECHA',
            'cantidad_disponible' => 1,
        ]);
        $this->assertDatabaseHas('detalle_ventas', [
            'venta_id' => $venta->id,
            'codigo_lote' => 'PROXIMO',
            'cantidad' => 2,
            'nombre_producto' => 'Producto prueba',
            'precio_unitario' => '2.35',
            'subtotal' => '4.70',
        ]);
        $this->assertDatabaseHas('detalle_ventas', [
            'venta_id' => $venta->id,
            'codigo_lote' => 'LEJANO',
            'cantidad' => 3,
            'subtotal' => '7.05',
        ]);
        $this->assertDatabaseHas('movimientos_inventario', [
            'venta_id' => $venta->id,
            'tipo' => MovimientoInventario::SALIDA,
            'lote_producto_id' => LoteProducto::query()->where('codigo_lote', 'PROXIMO')->value('id'),
        ]);
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $recepcionista->id,
            'accion' => Auditoria::CREAR,
            'tabla_afectada' => 'ventas',
            'registro_id' => $venta->id,
        ]);

        $producto->update(['nombre' => 'Nombre actualizado', 'precio_venta' => '9.99']);
        $this->get(route('ventas.comprobante', $venta))
            ->assertOk()
            ->assertSee($venta->folio)
            ->assertSee('Producto prueba')
            ->assertSee('2.35')
            ->assertDontSee('Nombre actualizado');
    }

    /**
     * La venta no persiste ningún cambio cuando otra línea solo tiene lotes vencidos.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_sale_with_an_expired_only_item_rolls_back_all_requested_items(): void
    {
        $recepcionista = $this->createUser('Recepcionista');
        $productoVigente = $this->createProducto([
            ['codigo_lote' => 'VIGENTE', 'fecha_caducidad' => '2027-01-01', 'cantidad_disponible' => 5],
        ]);
        $productoVencido = $this->createProducto([
            ['codigo_lote' => 'CADUCADO', 'fecha_caducidad' => '2026-10-26', 'cantidad_disponible' => 2],
        ]);

        $this->actingAs($recepcionista)
            ->post(route('ventas.store'), [
                'metodo_pago' => Venta::EFECTIVO,
                'items' => [
                    ['producto_id' => $productoVigente->id, 'cantidad' => 2],
                    ['producto_id' => $productoVencido->id, 'cantidad' => 1],
                ],
            ])
            ->assertSessionHasErrors([
                'items' => "No hay suficientes unidades vigentes de «{$productoVencido->nombre}».",
            ]);

        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('detalle_ventas', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(5, $productoVigente->fresh()->stock);
        $this->assertSame(2, $productoVencido->fresh()->stock);
    }

    /**
     * Una venta no puede exceder el stock total del producto aunque existan lotes vigentes.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_sale_rejects_quantity_above_total_stock(): void
    {
        $recepcionista = $this->createUser('Recepcionista');
        $producto = $this->createProducto([
            ['codigo_lote' => 'DISPONIBLE', 'fecha_caducidad' => null, 'cantidad_disponible' => 3],
        ]);

        $this->actingAs($recepcionista)
            ->post(route('ventas.store'), [
                'metodo_pago' => Venta::EFECTIVO,
                'items' => [
                    ['producto_id' => $producto->id, 'cantidad' => 4],
                ],
            ])
            ->assertSessionHasErrors([
                'items' => "Stock insuficiente para «{$producto->nombre}». Disponible: 3.",
            ]);

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(3, $producto->fresh()->stock);
    }

    /**
     * Un producto inactivo no se puede vender aunque conserve lotes con stock.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_sale_rejects_inactive_product_without_changing_stock(): void
    {
        $recepcionista = $this->createUser('Recepcionista');
        $producto = $this->createProducto([
            ['codigo_lote' => 'ACTIVO-ANTES', 'fecha_caducidad' => null, 'cantidad_disponible' => 3],
        ], activo: false);

        $this->actingAs($recepcionista)
            ->post(route('ventas.store'), [
                'metodo_pago' => Venta::EFECTIVO,
                'items' => [
                    ['producto_id' => $producto->id, 'cantidad' => 1],
                ],
            ])
            ->assertSessionHasErrors([
                'items' => "El producto «{$producto->nombre}» está inactivo.",
            ]);

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(3, $producto->fresh()->stock);
    }

    /**
     * Solo Administrador y Recepcionista pueden abrir y procesar ventas.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_veterinarian_cannot_access_or_process_sales(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $producto = $this->createProducto([
            ['codigo_lote' => 'VIGENTE', 'fecha_caducidad' => null, 'cantidad_disponible' => 3],
        ]);

        $this->actingAs($veterinario)
            ->get(route('ventas.index'))
            ->assertForbidden();

        $this->post(route('ventas.store'), [
            'metodo_pago' => Venta::EFECTIVO,
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 1],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(3, $producto->fresh()->stock);
    }

    /**
     * El formulario y la creación de venta exigen autenticación.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_guest_is_redirected_to_login_for_sales_pages(): void
    {
        $this->get(route('ventas.index'))->assertRedirect(route('login'));
        $this->get(route('ventas.create'))->assertRedirect(route('login'));
        $this->post(route('ventas.store'), [])->assertRedirect(route('login'));
    }

    /**
     * Rechaza métodos de pago y campos de artículo que no pertenecen al contrato.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_sale_rejects_invalid_payment_method_and_unexpected_item_fields(): void
    {
        $recepcionista = $this->createUser('Recepcionista');
        $producto = $this->createProducto([
            ['codigo_lote' => 'VIGENTE', 'fecha_caducidad' => null, 'cantidad_disponible' => 3],
        ]);

        $this->actingAs($recepcionista)
            ->post(route('ventas.store'), [
                'metodo_pago' => 'bitcoin',
                'items' => [
                    ['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 0.01],
                ],
            ])
            ->assertSessionHasErrors(['metodo_pago', 'items.0']);

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(3, $producto->fresh()->stock);
    }

    /**
     * Crea o recupera el rol y persiste un usuario para el caso de prueba.
     *
     * @param  string  $nombreRol  Nombre del rol.
     * @return User Usuario persistido.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function createUser(string $nombreRol): User
    {
        $rol = Role::query()->firstOrCreate(
            ['nombre' => $nombreRol],
            ['descripcion' => 'Rol para pruebas de ventas.'],
        );

        return User::factory()->create(['rol_id' => $rol->id]);
    }

    /**
     * Crea un producto y sus lotes con las cantidades especificadas.
     *
     * @param  array<int, array{codigo_lote: string, fecha_caducidad: string|null, cantidad_disponible: int}>  $lotes  Lotes a crear.
     * @param  string  $precioVenta  Precio unitario usado en ventas.
     * @param  bool  $activo  Estado del producto.
     * @return Producto Producto persistido.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function createProducto(array $lotes, string $precioVenta = '2.35', bool $activo = true): Producto
    {
        $categoria = CategoriaProducto::query()->firstOrCreate(['nombre' => 'Pruebas POS']);
        $stock = array_sum(array_column($lotes, 'cantidad_disponible'));
        $producto = Producto::query()->create([
            'categoria_id' => $categoria->id,
            'codigo' => 'POS-'.fake()->unique()->numerify('#####'),
            'nombre' => 'Producto prueba',
            'unidad' => 'unidad',
            'precio_compra' => '1.00',
            'precio_venta' => $precioVenta,
            'stock' => $stock,
            'stock_minimo' => 0,
            'activo' => $activo,
        ]);

        foreach ($lotes as $lote) {
            $producto->lotes()->create([
                ...$lote,
                'costo_compra' => '1.00',
            ]);
        }

        return $producto;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\InventarioService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Coordina el registro de ventas y la presentación de comprobantes.
 *
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class VentaController extends Controller
{
    /**
     * Muestra las ventas recientes disponibles para el usuario autorizado.
     *
     * @return View Listado paginado de ventas.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function index(): View
    {
        return view('ventas.index', [
            'ventas' => Venta::query()
                ->with('usuario:id,nombre')
                ->orderByDesc('fecha_venta')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    /**
     * Presenta los productos activos que tienen lotes con stock vigente.
     *
     * @return View Formulario del punto de venta.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function create(): View
    {
        $productos = Producto::query()
            ->activos()
            ->withSum([
                'lotes as stock_vigente' => fn ($query) => $query
                    ->vendibles(today()->toDateString()),
            ], 'cantidad_disponible')
            ->whereHas('lotes', static function (Builder $query): void {
                $query->vendibles(today()->toDateString());
            })
            ->orderBy('nombre')
            ->get();

        return view('ventas.create', [
            'productos' => $productos,
            'metodosPago' => Venta::METODOS_PAGO,
        ]);
    }

    /**
     * Valida la solicitud y delega la venta atómica al servicio de inventario.
     *
     * @param  Request  $request  Solicitud con artículos y medio de pago.
     * @param  InventarioService  $inventario  Servicio transaccional de stock y ventas.
     * @return RedirectResponse Redirección al comprobante de la venta confirmada.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function store(Request $request, InventarioService $inventario): RedirectResponse
    {
        $datos = $request->validate([
            'metodo_pago' => ['required', Rule::in(array_keys(Venta::METODOS_PAGO))],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['required', 'array:producto_id,cantidad'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
        ], [
            'metodo_pago.required' => 'Selecciona el medio de pago.',
            'items.required' => 'Agrega al menos un producto a la venta.',
            'items.min' => 'Agrega al menos un producto a la venta.',
            'items.*.producto_id.exists' => 'Uno de los productos seleccionados ya no existe.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos una unidad.',
        ]);

        $usuario = $request->user();
        abort_unless($usuario instanceof User, 403);

        $venta = $inventario->vender(
            $datos['items'],
            $datos['metodo_pago'],
            $usuario,
        );

        return redirect()
            ->route('ventas.comprobante', $venta)
            ->with('status', 'La venta se registró correctamente.');
    }

    /**
     * Muestra el comprobante inmutable de una venta existente.
     *
     * @param  Venta  $venta  Venta resuelta mediante route model binding.
     * @return View Comprobante con su usuario y desglose histórico.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function comprobante(Venta $venta): View
    {
        return view('ventas.comprobante', [
            'venta' => $venta->load(['usuario:id,nombre', 'detalles']),
            'metodosPago' => Venta::METODOS_PAGO,
        ]);
    }
}

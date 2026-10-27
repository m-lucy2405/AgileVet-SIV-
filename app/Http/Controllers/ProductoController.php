<?php

namespace App\Http\Controllers;

use App\Models\CategoriaProducto;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Catálogo de productos del inventario: lista con filtros para todos los usuarios;
 * crear, editar, activar y desactivar solo para el administrador (lo controlan las rutas).
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class ProductoController extends Controller
{
    /**
     * Días hacia adelante para considerar que un producto está por vencer.
     */
    public const DIAS_POR_VENCER = 30;

    /**
     * Lista los productos con búsqueda y filtros.
     */
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'categoria_id' => ['nullable', 'integer', Rule::exists('categorias_productos', 'id')],
            'alerta' => ['nullable', Rule::in(['stock_bajo', 'por_vencer'])],
            'estado' => ['nullable', Rule::in(['activos', 'inactivos', 'todos'])],
        ]);

        $buscar = trim($filtros['buscar'] ?? '');
        $estado = $filtros['estado'] ?? 'activos';

        $productos = Producto::query()
            ->with([
                'categoria',
                'lotes' => fn ($query) => $query->where('cantidad_disponible', '>', 0),
            ])
            ->when($buscar !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigo', 'like', "%{$buscar}%")))
            ->when($filtros['categoria_id'] ?? null, fn (Builder $query, $id) => $query->where('categoria_id', $id))
            ->when(($filtros['alerta'] ?? null) === 'stock_bajo', fn (Builder $query) => $query->stockBajo())
            ->when(($filtros['alerta'] ?? null) === 'por_vencer', fn (Builder $query) => $query->porVencer(self::DIAS_POR_VENCER))
            ->when($estado === 'activos', fn (Builder $query) => $query->where('activo', true))
            ->when($estado === 'inactivos', fn (Builder $query) => $query->where('activo', false))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('inventario.index', [
            'productos' => $productos,
            'categorias' => CategoriaProducto::query()->orderBy('nombre')->get(),
            'filtros' => [...$filtros, 'buscar' => $buscar, 'estado' => $estado],
            'resumen' => [
                'activos' => Producto::query()->activos()->count(),
                'stock_bajo' => Producto::query()->activos()->stockBajo()->count(),
                'por_vencer' => Producto::query()->activos()->porVencer(self::DIAS_POR_VENCER)->count(),
            ],
            'esAdministrador' => $request->user()?->role?->nombre === 'Administrador',
        ]);
    }

    /**
     * Kárdex del producto: datos, alertas y movimientos con filtros por tipo y fechas.
     */
    public function show(Request $request, Producto $producto): View
    {
        $filtros = $request->validate([
            'tipo' => ['nullable', Rule::in(array_keys(MovimientoInventario::TIPOS))],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);

        $movimientos = $producto->movimientos()
            ->with('usuario:id,nombre')
            ->when($filtros['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when($filtros['desde'] ?? null, fn ($query, $desde) => $query->whereDate('fecha_movimiento', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($query, $hasta) => $query->whereDate('fecha_movimiento', '<=', $hasta));

        // Totales de lo filtrado: cuánto entró y cuánto salió según la variación del stock.
        $todos = (clone $movimientos)->get([
            'tipo',
            'cantidad',
            'stock_anterior',
            'stock_nuevo',
        ]);

        $totales = [
            'entradas' => $todos
                ->filter(fn ($m) => $m->stock_nuevo > $m->stock_anterior)
                ->sum('cantidad'),
            'salidas' => $todos
                ->filter(fn ($m) => $m->stock_nuevo < $m->stock_anterior)
                ->sum('cantidad'),
            'cantidad' => $todos->count(),
        ];

        return view('inventario.show', [
            'producto' => $producto->load([
                'categoria',
                'lotes' => fn ($query) => $query->where('cantidad_disponible', '>', 0),
            ]),
            'movimientos' => $movimientos->paginate(20)->withQueryString(),
            'totales' => $totales,
            'filtros' => $filtros,
            'tipos' => MovimientoInventario::TIPOS,
            'esAdministrador' => $request->user()?->role?->nombre === 'Administrador',
        ]);
    }

    /**
     * Formulario para crear un producto.
     */
    public function create(): View
    {
        return view('inventario.create', [
            'categorias' => CategoriaProducto::query()->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Guarda un producto nuevo; el stock inicial queda como primera entrada del kárdex.
     */
    public function store(Request $request, InventarioService $inventario): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->reglas(),
            'stock_inicial' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'codigo_lote_inicial' => ['nullable', 'string', 'max:100'],
        ], $this->mensajes());

        $stockInicial = (int) ($validated['stock_inicial'] ?? 0);
        $codigoLoteInicial = $validated['codigo_lote_inicial'] ?? null;
        unset($validated['stock_inicial']);
        unset($validated['codigo_lote_inicial']);

        DB::transaction(function () use ($validated, $stockInicial, $codigoLoteInicial, $inventario, $request): void {
            $producto = Producto::query()->create([
                ...$validated,
                'stock' => 0,
                'activo' => true,
            ]);

            if ($stockInicial > 0) {
                $inventario->registrar(
                    $producto,
                    MovimientoInventario::ENTRADA,
                    $stockInicial,
                    'Inventario inicial',
                    $request->user(),
                    $codigoLoteInicial ?? 'INICIAL-'.$producto->codigo,
                    $producto->fecha_vencimiento?->toDateString(),
                    $producto->precio_compra,
                );
            }
        });

        return redirect()
            ->route('productos.index')
            ->with('status', 'El producto se registró correctamente.');
    }

    /**
     * Formulario para editar un producto (sin el stock, que cambia con movimientos).
     */
    public function edit(Producto $producto): View
    {
        return view('inventario.edit', [
            'producto' => $producto,
            'categorias' => CategoriaProducto::query()->orderBy('nombre')->get(),
        ]);
    }

    /**
     * Actualiza los datos del producto.
     */
    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $producto->update(
            $request->validate($this->reglas($producto), $this->mensajes())
        );

        return redirect()
            ->route('productos.index')
            ->with('status', 'El producto se actualizó correctamente.');
    }

    /**
     * Activa o desactiva el producto (no se borran para conservar el historial).
     */
    public function cambiarEstado(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => ! $producto->activo]);

        return redirect()
            ->back()
            ->with(
                'status',
                $producto->activo
                    ? "El producto «{$producto->nombre}» se activó."
                    : "El producto «{$producto->nombre}» se desactivó."
            );
    }

    /**
     * Reglas comunes de crear y editar. Al editar, el código puede repetirse con el propio producto.
     */
    private function reglas(?Producto $producto = null): array
    {
        return [
            'categoria_id' => ['required', 'integer', Rule::exists('categorias_productos', 'id')],
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos', 'codigo')->ignore($producto)],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'unidad' => ['required', 'string', 'max:30'],
            'precio_compra' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock_minimo' => ['required', 'integer', 'min:0', 'max:1000000'],
            'fecha_vencimiento' => ['nullable', 'date'],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    private function mensajes(): array
    {
        return [
            'categoria_id.required' => 'Elige una categoría.',
            'codigo.required' => 'Escribe el código del producto.',
            'codigo.unique' => 'Ya existe un producto con ese código.',
            'nombre.required' => 'Escribe el nombre del producto.',
            'unidad.required' => 'Escribe la unidad (por ejemplo, caja o frasco).',
            'precio_compra.required' => 'Escribe el precio de compra.',
            'precio_venta.required' => 'Escribe el precio de venta.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'stock_minimo.required' => 'Escribe el stock mínimo (puede ser 0).',
            'stock_inicial.min' => 'El stock inicial no puede ser negativo.',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Registra entradas, salidas y ajustes de inventario.
 * Solo el administrador puede hacerlo (lo controlan las rutas).
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class MovimientoInventarioController extends Controller
{
    /**
     * Motivos sugeridos para cada tipo de movimiento.
     *
     * @var array<string, array<int, string>>
     */
    public const MOTIVOS = [
        MovimientoInventario::ENTRADA => ['Compra a proveedor', 'Devolución de cliente', 'Donación'],
        MovimientoInventario::SALIDA => ['Uso en consulta', 'Producto vencido', 'Producto dañado', 'Pérdida'],
        MovimientoInventario::AJUSTE => ['Conteo físico de inventario', 'Corrección de error de registro'],
    ];

    /**
     * Formulario para registrar un movimiento de un producto.
     */
    public function create(Producto $producto): View
    {
        return view('inventario.movimiento', [
            'producto' => $producto->load('categoria'),
            'tipos' => MovimientoInventario::TIPOS,
            'motivos' => self::MOTIVOS,
        ]);
    }

    /**
     * Valida y registra el movimiento usando el servicio de inventario.
     */
    public function store(Request $request, Producto $producto, InventarioService $inventario): RedirectResponse
    {
        if (! $producto->activo) {
            throw ValidationException::withMessages([
                'tipo' => 'El producto está inactivo. Actívalo para registrar movimientos.',
            ]);
        }

        $tipo = $request->input('tipo');

        $validated = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(MovimientoInventario::TIPOS))],
            // En un ajuste se escribe el stock contado, que sí puede ser 0
            'cantidad' => ['required', 'integer', $tipo === MovimientoInventario::AJUSTE ? 'min:0' : 'min:1', 'max:1000000'],
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            'tipo.required' => 'Elige el tipo de movimiento.',
            'cantidad.required' => 'Escribe la cantidad.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => $tipo === MovimientoInventario::AJUSTE
                ? 'El stock contado no puede ser negativo.'
                : 'La cantidad debe ser al menos 1.',
            'motivo.required' => 'Escribe el motivo del movimiento.',
        ]);

        $movimiento = $inventario->registrar(
            $producto,
            $validated['tipo'],
            (int) $validated['cantidad'],
            $validated['motivo'],
            $request->user(),
        );

        return redirect()
            ->route('productos.index')
            ->with('status', sprintf(
                '%s registrada: %s. Stock de «%s»: %d → %d %s.',
                $movimiento->nombreTipo(),
                $validated['motivo'],
                $producto->nombre,
                $movimiento->stock_anterior,
                $movimiento->stock_nuevo,
                $producto->unidad,
            ));
    }
}
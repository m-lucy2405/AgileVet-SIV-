<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único punto del sistema que cambia el stock de un producto.
 * Cada cambio queda registrado como un movimiento en el kárdex.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class InventarioService
{
    /**
     * Registra un movimiento y actualiza el stock del producto.
     *
     * - Entrada: suma la cantidad.
     * - Salida: resta la cantidad; no permite que el stock quede negativo.
     * - Ajuste: la cantidad es el stock real contado; el sistema calcula la diferencia.
     *
     * @param  Producto  $producto  Producto al que se le registra el movimiento.
     * @param  string  $tipo  entrada, salida o ajuste.
     * @param  int  $cantidad  Unidades (o stock contado, si es ajuste).
     * @param  string  $motivo  Razón del movimiento.
     * @param  User  $usuario  Usuario que registra el movimiento.
     * @return MovimientoInventario Movimiento registrado.
     *
     * @throws ValidationException Si la salida deja el stock negativo o el ajuste no cambia nada.
     */
    public function registrar(Producto $producto, string $tipo, int $cantidad, string $motivo, User $usuario): MovimientoInventario
    {
        return DB::transaction(function () use ($producto, $tipo, $cantidad, $motivo, $usuario): MovimientoInventario {
            // Se bloquea el producto para que dos movimientos simultáneos no calculen mal el stock
            $bloqueado = Producto::query()->whereKey($producto->getKey())->lockForUpdate()->firstOrFail();
            $anterior = $bloqueado->stock;

            $nuevo = match ($tipo) {
                MovimientoInventario::ENTRADA => $anterior + $cantidad,
                MovimientoInventario::SALIDA => $anterior - $cantidad,
                MovimientoInventario::AJUSTE => $cantidad,
            };

            if ($nuevo < 0) {
                throw ValidationException::withMessages([
                    'cantidad' => "No hay suficiente stock. Disponible: {$anterior} {$bloqueado->unidad}.",
                ]);
            }

            if ($tipo === MovimientoInventario::AJUSTE && $nuevo === $anterior) {
                throw ValidationException::withMessages([
                    'cantidad' => 'El stock contado es igual al actual; no hay nada que ajustar.',
                ]);
            }

            $bloqueado->update(['stock' => $nuevo]);

            // Se actualiza también el objeto que recibió el método, para que quien lo llamó vea el stock nuevo
            $producto->setRawAttributes($bloqueado->getAttributes(), true);

            return MovimientoInventario::query()->create([
                'producto_id' => $bloqueado->id,
                'usuario_id' => $usuario->id,
                'tipo' => $tipo,
                'cantidad' => abs($nuevo - $anterior),
                'stock_anterior' => $anterior,
                'stock_nuevo' => $nuevo,
                'motivo' => $motivo,
                'fecha_movimiento' => now(),
            ]);
        });
    }
}
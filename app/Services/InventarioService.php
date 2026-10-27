<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\DetalleVenta;
use App\Models\LoteProducto;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Centraliza cambios de stock, asignación FEFO y confirmación de ventas.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class InventarioService
{
    /**
     * Registra entradas, salidas y ajustes distribuyéndolos entre lotes.
     *
     * @param  Producto  $producto  Producto al que se le registra el movimiento.
     * @param  string  $tipo  entrada, salida o ajuste.
     * @param  int  $cantidad  Unidades (o stock contado, si es ajuste).
     * @param  string  $motivo  Razón del movimiento.
     * @param  User  $usuario  Usuario que registra el movimiento.
     * @param  string|null  $codigoLote  Código físico informado para una entrada.
     * @param  string|null  $fechaCaducidad  Fecha de caducidad opcional del lote.
     * @param  string|null  $costoCompra  Costo unitario informado para el lote.
     * @return MovimientoInventario Movimiento registrado.
     *
     * @throws ValidationException Si la salida deja el stock negativo o el ajuste no cambia nada.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function registrar(
        Producto $producto,
        string $tipo,
        int $cantidad,
        string $motivo,
        User $usuario,
        ?string $codigoLote = null,
        ?string $fechaCaducidad = null,
        ?string $costoCompra = null,
    ): MovimientoInventario {
        return DB::transaction(function () use (
            $producto,
            $tipo,
            $cantidad,
            $motivo,
            $usuario,
            $codigoLote,
            $fechaCaducidad,
            $costoCompra,
        ): MovimientoInventario {
            $bloqueado = Producto::query()
                ->whereKey($producto->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $anterior = $bloqueado->stock;

            if ($cantidad < 0 || ($tipo !== MovimientoInventario::AJUSTE && $cantidad === 0)) {
                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad del movimiento no es válida.',
                ]);
            }

            $nuevo = match ($tipo) {
                MovimientoInventario::ENTRADA => $anterior + $cantidad,
                MovimientoInventario::SALIDA => $anterior - $cantidad,
                MovimientoInventario::AJUSTE => $cantidad,
                default => throw ValidationException::withMessages([
                    'tipo' => 'El tipo de movimiento no es válido.',
                ]),
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

            $movimientos = match ($tipo) {
                MovimientoInventario::ENTRADA => $this->registrarEntrada(
                    $bloqueado,
                    $cantidad,
                    $motivo,
                    $usuario,
                    $codigoLote,
                    $fechaCaducidad,
                    $costoCompra,
                ),
                MovimientoInventario::SALIDA => $this->descontarDeLotes(
                    $bloqueado,
                    $cantidad,
                    $motivo,
                    $usuario,
                ),
                MovimientoInventario::AJUSTE => $nuevo > $anterior
                    ? $this->registrarEntrada(
                        $bloqueado,
                        $nuevo - $anterior,
                        $motivo,
                        $usuario,
                        $codigoLote,
                        $fechaCaducidad,
                        $costoCompra,
                        MovimientoInventario::AJUSTE,
                    )
                    : $this->descontarDeLotes(
                        $bloqueado,
                        $anterior - $nuevo,
                        $motivo,
                        $usuario,
                        MovimientoInventario::AJUSTE,
                    ),
            };

            $bloqueado->update(['stock' => $nuevo]);
            $producto->setRawAttributes($bloqueado->getAttributes(), true);

            return $movimientos->last();
        });
    }

    /**
     * Confirma una venta y descuenta existencias de lotes vigentes en orden FEFO.
     *
     * @param  array<int, array{producto_id: int, cantidad: int}>  $articulos  Artículos solicitados.
     * @param  string  $metodoPago  Método de pago validado por el controlador.
     * @param  User  $usuario  Usuario que procesa la venta.
     * @return Venta Venta confirmada con sus detalles.
     *
     * @throws ValidationException Si los artículos no tienen stock vigente suficiente.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function vender(array $articulos, string $metodoPago, User $usuario): Venta
    {
        return DB::transaction(function () use ($articulos, $metodoPago, $usuario): Venta {
            $cantidades = [];

            foreach ($articulos as $articulo) {
                $productoId = (int) $articulo['producto_id'];
                $cantidades[$productoId] = ($cantidades[$productoId] ?? 0) + (int) $articulo['cantidad'];
            }

            ksort($cantidades);

            /** @var EloquentCollection<int, Producto> $productos */
            $productos = Producto::query()
                ->whereIn('id', array_keys($cantidades))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($productos->count() !== count($cantidades)) {
                throw ValidationException::withMessages([
                    'items' => 'Uno o más productos ya no están disponibles.',
                ]);
            }

            $lotesPorProducto = [];
            $totalCentavos = 0;
            foreach ($cantidades as $productoId => $cantidad) {
                $producto = $productos->get($productoId);

                if (! $producto->activo) {
                    throw ValidationException::withMessages([
                        'items' => "El producto «{$producto->nombre}» está inactivo.",
                    ]);
                }

                if ($producto->tieneStockInsuficiente($cantidad)) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente para «{$producto->nombre}». Disponible: {$producto->stock}.",
                    ]);
                }

                $lotes = $producto->lotes()
                    ->lockForUpdate()
                    ->get();
                $stockEnLotes = (int) $lotes->sum('cantidad_disponible');
                $disponibles = $lotes
                    ->filter(fn (LoteProducto $lote): bool => $lote->cantidad_disponible > 0
                        && ($lote->fecha_caducidad === null || ! $lote->fecha_caducidad->lt(today())))
                    ->sort(function (LoteProducto $primero, LoteProducto $segundo): int {
                        if ($primero->fecha_caducidad === null) {
                            return $segundo->fecha_caducidad === null ? $primero->id <=> $segundo->id : 1;
                        }

                        if ($segundo->fecha_caducidad === null) {
                            return -1;
                        }

                        return strcmp(
                            $primero->fecha_caducidad->toDateString(),
                            $segundo->fecha_caducidad->toDateString(),
                        )
                            ?: $primero->id <=> $segundo->id;
                    })
                    ->values();

                if ($stockEnLotes !== $producto->stock) {
                    throw ValidationException::withMessages([
                        'items' => "El inventario de «{$producto->nombre}» requiere conciliación antes de vender.",
                    ]);
                }

                if ($disponibles->sum('cantidad_disponible') < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "No hay suficientes unidades vigentes de «{$producto->nombre}».",
                    ]);
                }

                $lotesPorProducto[$productoId] = $disponibles;
                $totalCentavos += $this->precioEnCentavos((string) $producto->precio_venta) * $cantidad;
            }

            $fechaVenta = now();
            $venta = Venta::query()->create([
                'folio' => 'SIV-'.$fechaVenta->format('Ymd').'-'.Str::upper((string) Str::ulid()),
                'usuario_id' => $usuario->id,
                'metodo_pago' => $metodoPago,
                'fecha_venta' => $fechaVenta,
                'total' => $this->formatearCentavos($totalCentavos),
            ]);

            $detallesAuditados = [];
            foreach ($cantidades as $productoId => $cantidadSolicitada) {
                $producto = $productos->get($productoId);
                $restante = $cantidadSolicitada;
                $precioUnitario = (string) $producto->precio_venta;
                $stockAnterior = $producto->stock;

                foreach ($lotesPorProducto[$productoId] as $lote) {
                    if ($restante === 0) {
                        break;
                    }

                    $cantidadLote = min($restante, $lote->cantidad_disponible);
                    $lote->update([
                        'cantidad_disponible' => $lote->cantidad_disponible - $cantidadLote,
                    ]);
                    $stockNuevo = $stockAnterior - $cantidadLote;
                    $producto->update(['stock' => $stockNuevo]);

                    $detalle = DetalleVenta::query()->create([
                        'venta_id' => $venta->id,
                        'producto_id' => $producto->id,
                        'lote_producto_id' => $lote->id,
                        'codigo_producto' => $producto->codigo,
                        'nombre_producto' => $producto->nombre,
                        'codigo_lote' => $lote->codigo_lote,
                        'cantidad' => $cantidadLote,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $this->formatearCentavos(
                            $this->precioEnCentavos($precioUnitario) * $cantidadLote,
                        ),
                    ]);

                    $movimiento = MovimientoInventario::query()->create([
                        'producto_id' => $producto->id,
                        'lote_producto_id' => $lote->id,
                        'venta_id' => $venta->id,
                        'usuario_id' => $usuario->id,
                        'tipo' => MovimientoInventario::SALIDA,
                        'cantidad' => $cantidadLote,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $stockNuevo,
                        'motivo' => 'Venta '.$venta->folio,
                        'fecha_movimiento' => $fechaVenta,
                    ]);

                    $this->auditarMovimiento($movimiento, $usuario);
                    $detallesAuditados[] = [
                        'detalle_id' => $detalle->id,
                        'producto' => $detalle->nombre_producto,
                        'codigo_lote' => $detalle->codigo_lote,
                        'cantidad' => $detalle->cantidad,
                        'precio_unitario' => $detalle->precio_unitario,
                        'subtotal' => $detalle->subtotal,
                    ];
                    $stockAnterior = $stockNuevo;
                    $restante -= $cantidadLote;
                }
            }

            Auditoria::query()->create([
                'usuario_id' => $usuario->id,
                'accion' => Auditoria::CREAR,
                'tabla_afectada' => 'ventas',
                'registro_id' => $venta->id,
                'fecha_accion' => $fechaVenta,
                'descripcion' => 'Venta confirmada con descuento FEFO de existencias.',
                'valores_nuevos' => [
                    'folio' => $venta->folio,
                    'metodo_pago' => $venta->metodo_pago,
                    'total' => $venta->total,
                    'detalles' => $detallesAuditados,
                ],
            ]);

            return $venta->load(['usuario', 'detalles']);
        }, attempts: 3);
    }

    /**
     * Añade existencias a un lote o crea uno nuevo y registra la entrada.
     *
     * @param  Producto  $producto  Producto bloqueado para escritura.
     * @param  int  $cantidad  Unidades recibidas.
     * @param  string  $motivo  Motivo del movimiento.
     * @param  User  $usuario  Usuario que registra el cambio.
     * @param  string|null  $codigoLote  Código informado, o uno generado para el lote.
     * @param  string|null  $fechaCaducidad  Fecha de caducidad opcional.
     * @param  string|null  $costoCompra  Costo unitario opcional.
     * @param  string  $tipoMovimiento  Tipo persistido para el kárdex.
     * @return EloquentCollection<int, MovimientoInventario> Movimiento de entrada.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function registrarEntrada(
        Producto $producto,
        int $cantidad,
        string $motivo,
        User $usuario,
        ?string $codigoLote,
        ?string $fechaCaducidad,
        ?string $costoCompra,
        string $tipoMovimiento = MovimientoInventario::ENTRADA,
    ): EloquentCollection {
        $codigoLote ??= 'MOV-'.Str::upper((string) Str::ulid());

        if ($fechaCaducidad !== null && today()->gt($fechaCaducidad)) {
            throw ValidationException::withMessages([
                'fecha_caducidad' => 'No se puede recibir un lote ya vencido.',
            ]);
        }

        $lote = $producto->lotes()->where('codigo_lote', $codigoLote)->lockForUpdate()->first();
        if ($lote !== null) {
            if ($lote->fecha_caducidad?->toDateString() !== $fechaCaducidad) {
                throw ValidationException::withMessages([
                    'codigo_lote' => 'El código de lote ya existe con otra fecha de caducidad.',
                ]);
            }

            $lote->update([
                'cantidad_disponible' => $lote->cantidad_disponible + $cantidad,
            ]);
        } else {
            $lote = $producto->lotes()->create([
                'codigo_lote' => $codigoLote,
                'fecha_caducidad' => $fechaCaducidad,
                'cantidad_disponible' => $cantidad,
                'costo_compra' => $costoCompra ?? $producto->precio_compra,
            ]);
        }

        $stockAnterior = $producto->stock;
        $stockNuevo = $stockAnterior + $cantidad;
        $producto->update(['stock' => $stockNuevo]);

        $movimiento = MovimientoInventario::query()->create([
            'producto_id' => $producto->id,
            'lote_producto_id' => $lote->id,
            'usuario_id' => $usuario->id,
            'tipo' => $tipoMovimiento,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'motivo' => $motivo,
            'fecha_movimiento' => now(),
        ]);
        $this->auditarMovimiento($movimiento, $usuario);

        return new EloquentCollection([$movimiento]);
    }

    /**
     * Reduce lotes en orden de vencimiento para una salida administrativa.
     *
     * @param  Producto  $producto  Producto bloqueado para escritura.
     * @param  int  $cantidad  Unidades a retirar.
     * @param  string  $motivo  Motivo del movimiento.
     * @param  User  $usuario  Usuario que registra el cambio.
     * @param  string  $tipoMovimiento  Tipo persistido para el kárdex.
     * @return EloquentCollection<int, MovimientoInventario> Movimientos por lote consumido.
     *
     * @throws ValidationException Si el stock agregado no concuerda con los lotes.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function descontarDeLotes(
        Producto $producto,
        int $cantidad,
        string $motivo,
        User $usuario,
        string $tipoMovimiento = MovimientoInventario::SALIDA,
    ): EloquentCollection {
        $lotes = $producto->lotes()->lockForUpdate()->get();

        if ($lotes->sum('cantidad_disponible') !== $producto->stock) {
            throw ValidationException::withMessages([
                'cantidad' => 'El inventario requiere conciliación antes de registrar una salida.',
            ]);
        }

        $restante = $cantidad;
        $movimientos = new EloquentCollection;
        $stockAnterior = $producto->stock;

        foreach ($lotes as $lote) {
            if ($restante === 0) {
                break;
            }

            if ($lote->cantidad_disponible === 0) {
                continue;
            }

            $cantidadLote = min($restante, $lote->cantidad_disponible);
            $lote->update(['cantidad_disponible' => $lote->cantidad_disponible - $cantidadLote]);
            $stockNuevo = $stockAnterior - $cantidadLote;
            $producto->update(['stock' => $stockNuevo]);
            $movimiento = MovimientoInventario::query()->create([
                'producto_id' => $producto->id,
                'lote_producto_id' => $lote->id,
                'usuario_id' => $usuario->id,
                'tipo' => $tipoMovimiento,
                'cantidad' => $cantidadLote,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'motivo' => $motivo,
                'fecha_movimiento' => now(),
            ]);
            $this->auditarMovimiento($movimiento, $usuario);
            $movimientos->push($movimiento);
            $stockAnterior = $stockNuevo;
            $restante -= $cantidadLote;
        }

        if ($restante > 0) {
            throw ValidationException::withMessages([
                'cantidad' => "No hay suficiente stock. Disponible: {$producto->stock} {$producto->unidad}.",
            ]);
        }

        return $movimientos;
    }

    /**
     * Registra en auditoría un movimiento crítico con sus valores de stock.
     *
     * @param  MovimientoInventario  $movimiento  Movimiento persistido.
     * @param  User  $usuario  Actor responsable.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function auditarMovimiento(MovimientoInventario $movimiento, User $usuario): void
    {
        Auditoria::query()->create([
            'usuario_id' => $usuario->id,
            'accion' => Auditoria::CREAR,
            'tabla_afectada' => 'movimientos_inventario',
            'registro_id' => $movimiento->id,
            'fecha_accion' => $movimiento->fecha_movimiento,
            'descripcion' => $movimiento->motivo,
            'valores_nuevos' => [
                'producto_id' => $movimiento->producto_id,
                'lote_producto_id' => $movimiento->lote_producto_id,
                'venta_id' => $movimiento->venta_id,
                'tipo' => $movimiento->tipo,
                'cantidad' => $movimiento->cantidad,
                'stock_anterior' => $movimiento->stock_anterior,
                'stock_nuevo' => $movimiento->stock_nuevo,
            ],
        ]);
    }

    /**
     * Convierte un importe decimal de dos posiciones en centavos enteros.
     *
     * @param  string  $importe  Importe decimal.
     * @return int Importe como centavos.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function precioEnCentavos(string $importe): int
    {
        [$unidades, $centavos] = array_pad(explode('.', $importe, 2), 2, '00');

        return ((int) $unidades * 100) + (int) str_pad(substr($centavos, 0, 2), 2, '0');
    }

    /**
     * Convierte centavos enteros al formato decimal usado en la base de datos.
     *
     * @param  int  $centavos  Importe en centavos.
     * @return string Importe decimal con dos posiciones.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function formatearCentavos(int $centavos): string
    {
        return intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}

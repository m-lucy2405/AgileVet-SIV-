<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de inventario (entrada, salida o ajuste) de un producto.
 * Los movimientos no se editan ni se borran: son el historial del stock.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    public const ENTRADA = 'entrada';

    public const SALIDA = 'salida';

    public const AJUSTE = 'ajuste';

    /**
     * Nombre legible de cada tipo de movimiento.
     *
     * @var array<string, string>
     */
    public const TIPOS = [
        self::ENTRADA => 'Entrada',
        self::SALIDA => 'Salida',
        self::AJUSTE => 'Ajuste',
    ];

    protected $fillable = [
        'producto_id',
        'lote_producto_id',
        'usuario_id',
        'venta_id',
        'tipo',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'motivo',
        'fecha_movimiento',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'stock_anterior' => 'integer',
        'stock_nuevo' => 'integer',
        'fecha_movimiento' => 'datetime',
    ];

    /**
     * Producto del movimiento.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Lote afectado por el movimiento, cuando existe.
     *
     * @return BelongsTo<LoteProducto, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteProducto::class, 'lote_producto_id');
    }

    /**
     * Venta que originó el movimiento de salida, cuando existe.
     *
     * @return BelongsTo<Venta, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Usuario que registró el movimiento.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Nombre legible del tipo (por ejemplo, "Entrada").
     */
    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }
}

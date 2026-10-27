<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea histórica de una venta asociada con el producto y lote descontados.
 *
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class DetalleVenta extends Model
{
    use Auditable;

    protected $table = 'detalle_ventas';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'lote_producto_id',
        'codigo_producto',
        'nombre_producto',
        'codigo_lote',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Venta a la que pertenece la línea.
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
     * Producto al que corresponde la línea.
     *
     * @return BelongsTo<Producto, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Lote del que salieron las unidades vendidas.
     *
     * @return BelongsTo<LoteProducto, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteProducto::class, 'lote_producto_id');
    }
}

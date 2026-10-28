<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lote de existencias de un producto con fecha de caducidad y costo propios.
 *
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class LoteProducto extends Model
{
    protected $table = 'lotes_producto';

    protected $fillable = [
        'producto_id',
        'codigo_lote',
        'fecha_caducidad',
        'cantidad_disponible',
        'costo_compra',
    ];

    protected $casts = [
        'fecha_caducidad' => 'date',
        'cantidad_disponible' => 'integer',
        'costo_compra' => 'decimal:2',
    ];

    /**
     * Producto propietario del lote.
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
     * Detalles de venta que consumieron este lote.
     *
     * @return HasMany<DetalleVenta, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    /**
     * Movimientos de inventario asociados al lote.
     *
     * @return HasMany<MovimientoInventario, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /**
     * Filtra lotes con cantidad y caducidad válidas para una venta en la fecha indicada.
     *
     * @param  Builder<LoteProducto>  $query  Consulta de lotes.
     * @param  string  $fecha  Fecha local de la venta en formato Y-m-d.
     * @return Builder<LoteProducto> Consulta filtrada.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function scopeDisponiblesParaVenta(Builder $query, string $fecha): Builder
    {
        return $query->vendibles($fecha)
            ->orderByRaw('fecha_caducidad IS NULL')
            ->orderBy('fecha_caducidad')
            ->orderBy('id');
    }

    /**
     * Filtra lotes con cantidad y caducidad válidas para venta.
     *
     * @param  Builder<LoteProducto>  $query  Consulta de lotes.
     * @param  string  $fecha  Fecha local de la venta en formato Y-m-d.
     * @return Builder<LoteProducto> Consulta filtrada sin orden predeterminado.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function scopeVendibles(Builder $query, string $fecha): Builder
    {
        return $query
            ->where('cantidad_disponible', '>', 0)
            ->where(function (Builder $query) use ($fecha): void {
                $query->whereNull('fecha_caducidad')
                    ->orWhereDate('fecha_caducidad', '>=', $fecha);
            });
    }
}

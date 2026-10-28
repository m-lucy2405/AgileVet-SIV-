<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto del inventario. El stock solo cambia mediante movimientos.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class Producto extends Model
{
    use Auditable;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'codigo',
        'nombre',
        'descripcion',
        'unidad',
        'precio_compra',
        'precio_venta',
        'stock',
        'stock_minimo',
        'fecha_vencimiento',
        'activo',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'fecha_vencimiento' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Categoría del producto.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    /**
     * Movimientos del producto, del más reciente al más antiguo.
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class)
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id');
    }

    /**
     * Lotes asociados al producto, ordenados por fecha de caducidad.
     *
     * @return HasMany<LoteProducto, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function lotes(): HasMany
    {
        return $this->hasMany(LoteProducto::class)
            ->orderByRaw('fecha_caducidad IS NULL')
            ->orderBy('fecha_caducidad')
            ->orderBy('id');
    }

    /**
     * Solo productos activos.
     */
    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true);
    }

    /**
     * Productos con stock igual o menor al mínimo.
     */
    public function scopeStockBajo(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    /**
     * Productos que vencen dentro de los próximos días (incluye los ya vencidos).
     */
    public function scopePorVencer(Builder $query, int $dias = 30): void
    {
        $query->whereHas('lotes', fn (Builder $query): Builder => $query
            ->where('cantidad_disponible', '>', 0)
            ->whereNotNull('fecha_caducidad')
            ->whereDate('fecha_caducidad', '<=', today()->addDays($dias)));
    }

    /**
     * Indica si el stock está en el mínimo o por debajo.
     */
    public function tieneStockBajo(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }

    /**
     * Indica si el producto tiene menos stock disponible que el solicitado.
     *
     * @param  int  $cantidad  Cantidad solicitada.
     * @return bool Verdadero si el stock total es insuficiente.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function tieneStockInsuficiente(int $cantidad): bool
    {
        return $this->stock < $cantidad;
    }

    /**
     * Indica si el producto ya venció.
     */
    public function estaVencido(): bool
    {
        return $this->lotes()
            ->where('cantidad_disponible', '>', 0)
            ->whereNotNull('fecha_caducidad')
            ->whereDate('fecha_caducidad', '<', today())
            ->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta de mostrador confirmada e inmutable.
 *
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class Venta extends Model
{
    public const EFECTIVO = 'efectivo';

    public const TARJETA = 'tarjeta';

    public const TRANSFERENCIA = 'transferencia';

    /**
     * Medios de pago permitidos en el MVP.
     *
     * @var array<string, string>
     */
    public const METODOS_PAGO = [
        self::EFECTIVO => 'Efectivo',
        self::TARJETA => 'Tarjeta',
        self::TRANSFERENCIA => 'Transferencia',
    ];

    protected $table = 'ventas';

    protected $fillable = [
        'folio',
        'usuario_id',
        'metodo_pago',
        'fecha_venta',
        'total',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'total' => 'decimal:2',
    ];

    /**
     * Usuario que procesó la venta.
     *
     * @return BelongsTo<User, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Artículos incluidos en la venta.
     *
     * @return HasMany<DetalleVenta, $this>
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class)->orderBy('id');
    }
}

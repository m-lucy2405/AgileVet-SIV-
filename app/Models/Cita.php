<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'mascota_id',
        'veterinario_id',
        'fecha_cita',
        'estado',
        'motivo',
        'observaciones',
    ];

    protected $casts = [
        'fecha_cita' => 'datetime',
    ];

    /**
     * Relación: cada cita pertenece a una mascota.
     */
    public function mascota(): BelongsTo
    {
        return $this->belongsTo(
            Mascota::class,
            'mascota_id'
        );
    }

    /**
     * Relación: cada cita pertenece a un veterinario.
     */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'veterinario_id'
        );
    }

    /**
     * Filtrar citas pasadas.
     */
    public function scopePasadas(Builder $query): void
    {
        $query->where('fecha_cita', '<', now());
    }

    /**
     * Filtrar citas futuras.
     */
    public function scopeFuturas(Builder $query): void
    {
        $query->where('fecha_cita', '>', now());
    }

    /**
     * Filtrar citas de una fecha específica.
     */
    public function scopePorFecha(
        Builder $query,
        string $fecha
    ): void {
        $query->whereDate('fecha_cita', $fecha);
    }

    /**
     * Filtrar citas entre dos fechas y horas.
     */
    public function scopeEntreFechas(
        Builder $query,
        string $inicio,
        string $fin
    ): void {
        $query->whereBetween('fecha_cita', [
            $inicio,
            $fin,
        ]);
    }
}

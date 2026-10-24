<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * Relación: consulta registrada a partir de esta cita, si ya fue atendida.
     */
    public function consulta(): HasOne
    {
        return $this->hasOne(
            Consulta::class,
            'cita_id'
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

    /**
     * Filtra citas de duración fija que se solapan con el intervalo solicitado.
     *
     * La tabla solo almacena el inicio de cada cita. Al tener todas la misma
     * duración, el cruce se resuelve con inicio existente < fin solicitado y
     * inicio existente > inicio solicitado - duración.
     *
     * @param  Builder<Cita>  $query  Consulta Eloquent que se filtrará.
     * @param  CarbonImmutable  $start  Inicio del intervalo solicitado.
     * @param  CarbonImmutable  $end  Fin exclusivo del intervalo solicitado.
     * @param  int  $existingDurationMinutes  Duración de las citas existentes en minutos.
     * @return Builder<Cita> Consulta limitada a citas con intervalos solapados.
     */
    public function scopeOverlappingInterval(
        Builder $query,
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $existingDurationMinutes,
    ): Builder {
        return $query
            ->where(
                'fecha_cita',
                '<',
                $end->format('Y-m-d H:i:s'),
            )
            ->where(
                'fecha_cita',
                '>',
                $start
                    ->subMinutes($existingDurationMinutes)
                    ->format('Y-m-d H:i:s'),
            );
    }
}
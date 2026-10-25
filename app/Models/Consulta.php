<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Consulta veterinaria registrada en el expediente de una mascota.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class Consulta extends Model
{
    use Auditable;

    protected $table = 'consultas';

    protected $fillable = [
        'expediente_id',
        'veterinario_id',
        'cita_id',
        'fecha_consulta',
        'motivo',
        'peso_kg',
        'temperatura_c',
        'diagnostico',
        'tratamiento_indicado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_consulta' => 'datetime',
        'peso_kg' => 'decimal:2',
        'temperatura_c' => 'decimal:1',
    ];

    /**
     * Expediente al que pertenece la consulta.
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /**
     * Veterinario que atendió la consulta.
     */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    /**
     * Cita que dio origen a la consulta, si existe.
     */
    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    /**
     * Medicamentos recetados en la consulta.
     */
    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class);
    }
}
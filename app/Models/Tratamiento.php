<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Medicamento recetado en una consulta.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class Tratamiento extends Model
{
    protected $table = 'tratamientos';

    protected $fillable = [
        'consulta_id',
        'medicamento',
        'dosis',
        'frecuencia',
        'duracion',
        'indicaciones',
    ];

    /**
     * Consulta en la que se recetó el medicamento.
     */
    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }
}
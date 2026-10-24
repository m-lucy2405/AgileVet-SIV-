<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vacuna aplicada a una mascota.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class Vacunacion extends Model
{
    // Se indica la tabla porque Laravel pluraliza en inglés ("vacunacions")
    protected $table = 'vacunaciones';

    protected $fillable = [
        'expediente_id',
        'nombre_vacuna',
        'fecha_aplicacion',
        'proxima_dosis',
        'observaciones',
    ];

    protected $casts = [
        'fecha_aplicacion' => 'date',
        'proxima_dosis' => 'date',
    ];

    /**
     * Expediente al que pertenece la vacuna.
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}
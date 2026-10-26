<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Desparasitación aplicada a una mascota.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class Desparasitacion extends Model
{
    use Auditable;

    // Se indica la tabla porque Laravel pluraliza en inglés ("desparasitacions")
    protected $table = 'desparasitaciones';

    protected $fillable = [
        'expediente_id',
        'producto',
        'dosis',
        'fecha_aplicacion',
        'proxima_aplicacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_aplicacion' => 'date',
        'proxima_aplicacion' => 'date',
    ];

    /**
     * Expediente al que pertenece la desparasitación.
     */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}
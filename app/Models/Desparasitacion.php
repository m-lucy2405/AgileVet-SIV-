<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Desparasitación aplicada a una mascota.
 * El producto se copia del catálogo al registrarla, para que el historial no cambie
 * aunque después se edite el catálogo.
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
        'catalogo_desparasitante_id',
        'producto',
        'dosis',
        'fecha_aplicacion',
        'proxima_aplicacion',
        'cita_refuerzo_id',
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

    /**
     * Desparasitante del catálogo del que se tomó el nombre y el intervalo.
     */
    public function catalogo(): BelongsTo
    {
        return $this->belongsTo(CatalogoDesparasitante::class, 'catalogo_desparasitante_id');
    }

    /**
     * Cita de refuerzo agendada automáticamente.
     */
    public function citaRefuerzo(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_refuerzo_id');
    }
}
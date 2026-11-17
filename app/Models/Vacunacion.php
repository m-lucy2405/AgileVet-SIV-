<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vacuna aplicada a una mascota.
 * El nombre se copia del catálogo al registrarla, para que el historial no cambie
 * aunque después se edite el catálogo.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class Vacunacion extends Model
{
    use Auditable;

    // Se indica la tabla porque Laravel pluraliza en inglés ("vacunacions")
    protected $table = 'vacunaciones';

    protected $fillable = [
        'expediente_id',
        'catalogo_vacuna_id',
        'nombre_vacuna',
        'fecha_aplicacion',
        'proxima_dosis',
        'cita_refuerzo_id',
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

    /**
     * Vacuna del catálogo de la que se tomó el nombre y el intervalo.
     */
    public function catalogo(): BelongsTo
    {
        return $this->belongsTo(CatalogoVacuna::class, 'catalogo_vacuna_id');
    }

    /**
     * Cita de refuerzo agendada automáticamente.
     */
    public function citaRefuerzo(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_refuerzo_id');
    }
}
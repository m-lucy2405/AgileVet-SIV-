<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Expediente clínico único de una mascota.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class Expediente extends Model
{
    use Auditable;

    protected $table = 'expedientes';

    protected $fillable = [
        'mascota_id',
        'fecha_apertura',
        'antecedentes',
        'alergias',
        'enfermedades_cronicas',
        'observaciones',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
    ];

    /**
     * Mascota a la que pertenece el expediente.
     */
    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class);
    }

    /**
     * Consultas registradas, de la más reciente a la más antigua.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class)->orderByDesc('fecha_consulta');
    }

    /**
     * Vacunas aplicadas, de la más reciente a la más antigua.
     */
    public function vacunaciones(): HasMany
    {
        return $this->hasMany(Vacunacion::class)->orderByDesc('fecha_aplicacion');
    }

    /**
     * Desparasitaciones aplicadas, de la más reciente a la más antigua.
     */
    public function desparasitaciones(): HasMany
    {
        return $this->hasMany(Desparasitacion::class)->orderByDesc('fecha_aplicacion');
    }
}
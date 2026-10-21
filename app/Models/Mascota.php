<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mascota extends Model
{
    use HasFactory;

    protected $table = 'mascotas';

    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'cliente_id',
        'nombre',
        'especie',
        'raza',
        'sexo',
        'fecha_nacimiento',
        'color',
        'esterilizado',
    ];

    /**
     * Conversión de tipos de datos.
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'esterilizado' => 'boolean',
    ];

    /**
     * Una mascota pertenece a un cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }

    /**
     * Una mascota puede tener muchas citas.
     */
    public function citas(): HasMany
    {
        return $this->hasMany(
            Cita::class,
            'mascota_id'
        );
    }

    /**
     * Una mascota tiene un expediente clínico.
     */
    public function expediente(): HasOne
    {
        return $this->hasOne(
            Expediente::class,
            'mascota_id'
        );
    }
}


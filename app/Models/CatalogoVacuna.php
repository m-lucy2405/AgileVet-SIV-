<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Vacuna del catálogo, con cada cuántos días se aplica el refuerzo.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class CatalogoVacuna extends Model
{
    use Auditable;

    protected $table = 'catalogo_vacunas';

    protected $fillable = ['nombre', 'especie', 'intervalo_dias', 'activo'];

    protected $casts = [
        'intervalo_dias' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Activas y que aplican a la especie indicada (o a todas).
     */
    public function scopeParaEspecie(Builder $query, ?string $especie): void
    {
        $query->where('activo', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('especie')
                ->orWhere('especie', $especie))
            ->orderBy('nombre');
    }
}
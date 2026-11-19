<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Desparasitante del catálogo, con su dosis sugerida y cada cuántos días se reaplica.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class CatalogoDesparasitante extends Model
{
    use Auditable;

    protected $table = 'catalogo_desparasitantes';

    protected $fillable = ['nombre', 'especie', 'dosis_sugerida', 'intervalo_dias', 'activo'];

    protected $casts = [
        'intervalo_dias' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Activos y que aplican a la especie indicada (o a todas).
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

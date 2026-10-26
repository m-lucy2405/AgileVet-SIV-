<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría para agrupar los productos del inventario.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class CategoriaProducto extends Model
{
    use Auditable;

    protected $table = 'categorias_productos';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    /**
     * Productos de la categoría.
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
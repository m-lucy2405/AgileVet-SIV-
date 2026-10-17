<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa un rol operativo asignable a los usuarios del sistema.
 *
 * @property int $id
 */
#[Fillable(['nombre', 'descripcion'])]
class Role extends Model
{
    /**
     * Indica que la tabla roles no utiliza marcas de tiempo.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Obtiene los usuarios que tienen asignado este rol.
     *
     * @return HasMany<User, $this> Relación con los usuarios del rol.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}

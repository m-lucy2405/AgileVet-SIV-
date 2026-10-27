<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa a un cliente de la clínica veterinaria.
 *
 * @package App\Models
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'telefono',
        'direccion',
        'email',
    ];

    /**
     * Obtiene las mascotas asociadas al cliente.
     *
     * @package App\Models
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mascotas(): HasMany
    {
        return $this->hasMany(Mascota::class, 'cliente_id');
    }
}

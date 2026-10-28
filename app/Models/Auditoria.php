<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de una acción realizada en el sistema: quién, qué, dónde, cuándo
 * y los valores antes y después del cambio.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class Auditoria extends Model
{
    // Se indica la tabla porque Laravel buscaría "auditorias"
    protected $table = 'auditoria';

    /**
     * Acciones que se registran.
     */
    public const CREAR = 'crear';

    public const MODIFICAR = 'modificar';

    public const ELIMINAR = 'eliminar';

    public const INICIO_SESION = 'inicio_sesion';

    public const CIERRE_SESION = 'cierre_sesion';

    /**
     * Nombre legible de cada acción, para mostrarlo en pantalla.
     *
     * @var array<string, string>
     */
    public const NOMBRES_ACCIONES = [
        self::CREAR => 'Creación',
        self::MODIFICAR => 'Modificación',
        self::ELIMINAR => 'Eliminación',
        self::INICIO_SESION => 'Inicio de sesión',
        self::CIERRE_SESION => 'Cierre de sesión',
    ];

    /**
     * Nombre legible de cada tabla auditada, para mostrarlo en pantalla.
     *
     * @var array<string, string>
     */
    public const NOMBRES_TABLAS = [
        'users' => 'Usuarios',
        'citas' => 'Citas',
        'expedientes' => 'Expedientes',
        'consultas' => 'Consultas',
        'tratamientos' => 'Medicamentos recetados',
        'vacunaciones' => 'Vacunas',
        'desparasitaciones' => 'Desparasitaciones',
        'clientes' => 'Clientes',
        'mascotas' => 'Mascotas',
        'categorias_productos' => 'Categorías de productos',
        'productos' => 'Productos',
        'lotes_producto' => 'Lotes de inventario',
        'movimientos_inventario' => 'Movimientos de inventario',
        'ventas' => 'Ventas',
        'detalle_ventas' => 'Detalle de ventas',
    ];

    protected $fillable = [
        'usuario_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'fecha_accion',
        'descripcion',
        'valores_anteriores',
        'valores_nuevos',
        'ip',
    ];

    protected $casts = [
        'fecha_accion' => 'datetime',
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
    ];

    /**
     * Usuario que realizó la acción.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Nombre legible de la acción (por ejemplo, "Modificación").
     */
    public function nombreAccion(): string
    {
        return self::NOMBRES_ACCIONES[$this->accion] ?? ucfirst($this->accion);
    }

    /**
     * Nombre legible de la tabla afectada (por ejemplo, "Consultas").
     */
    public function nombreTabla(): string
    {
        return self::NOMBRES_TABLAS[$this->tabla_afectada] ?? $this->tabla_afectada;
    }
}

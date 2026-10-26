<?php

namespace App\Models\Concerns;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Registra en la auditoría cada creación, modificación y eliminación del modelo,
 * con el usuario, la fecha, la IP y los valores antes y después del cambio.
 *
 * Para usarlo, basta con agregar `use Auditable;` dentro del modelo.
 * Un modelo puede excluir campos extra con la propiedad:
 *     protected array $auditoriaExcluir = ['campo'];
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
trait Auditable
{
    /**
     * Campos que nunca se guardan en la auditoría.
     *
     * @var array<int, string>
     */
    private static array $camposNoAuditables = [
        'password',
        'password_hash',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    /**
     * Laravel llama este método automáticamente al iniciar el modelo
     * (por convención: "boot" + nombre del trait).
     */
    protected static function bootAuditable(): void
    {
        static::created(function (Model $modelo): void {
            $modelo->registrarAuditoria(
                Auditoria::CREAR,
                null,
                $modelo->filtrarCamposAuditables($modelo->getAttributes()),
            );
        });

        static::updated(function (Model $modelo): void {
            // Solo los campos que realmente cambiaron
            $nuevos = $modelo->filtrarCamposAuditables($modelo->getChanges());

            // Si solo cambiaron las fechas automáticas, no hay nada que registrar
            if ($nuevos === []) {
                return;
            }

            $anteriores = [];
            foreach (array_keys($nuevos) as $campo) {
                $anteriores[$campo] = $modelo->getRawOriginal($campo);
            }

            $modelo->registrarAuditoria(Auditoria::MODIFICAR, $anteriores, $nuevos);
        });

        static::deleted(function (Model $modelo): void {
            $modelo->registrarAuditoria(
                Auditoria::ELIMINAR,
                $modelo->filtrarCamposAuditables($modelo->getAttributes()),
                null,
            );
        });
    }

    /**
     * Guarda la acción en la auditoría si hay un usuario con sesión.
     *
     * @param  string  $accion  Acción realizada (crear, modificar o eliminar).
     * @param  array<string, mixed>|null  $anteriores  Valores antes del cambio.
     * @param  array<string, mixed>|null  $nuevos  Valores después del cambio.
     * @return void No retorna un valor.
     */
    public function registrarAuditoria(string $accion, ?array $anteriores, ?array $nuevos): void
    {
        $usuarioId = Auth::id();

        // La columna usuario_id es obligatoria: sin sesión (seeders, consola) no se registra
        if ($usuarioId === null) {
            return;
        }

        Auditoria::query()->create([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'tabla_afectada' => $this->getTable(),
            'registro_id' => $this->getKey(),
            'fecha_accion' => now(),
            'valores_anteriores' => $anteriores,
            'valores_nuevos' => $nuevos,
            'ip' => request()?->ip(),
        ]);
    }

    /**
     * Quita de los valores los campos que no se deben guardar en la auditoría.
     *
     * @param  array<string, mixed>  $valores  Valores del modelo.
     * @return array<string, mixed> Valores sin contraseñas, tokens ni fechas automáticas.
     */
    public function filtrarCamposAuditables(array $valores): array
    {
        $excluir = array_merge(
            self::$camposNoAuditables,
            property_exists($this, 'auditoriaExcluir') ? $this->auditoriaExcluir : [],
        );

        return array_diff_key($valores, array_flip($excluir));
    }
}
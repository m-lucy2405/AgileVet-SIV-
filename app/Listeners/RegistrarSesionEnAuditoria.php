<?php

namespace App\Listeners;

use App\Models\Auditoria;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Registra en la auditoría cada inicio y cierre de sesión.
 *
 * Laravel conecta estos métodos con sus eventos automáticamente: busca los
 * métodos que empiezan con "handle" y revisa el tipo de evento que reciben.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class RegistrarSesionEnAuditoria
{
    /**
     * Se ejecuta cuando un usuario inicia sesión correctamente.
     *
     * @param  Login  $evento  Evento de inicio de sesión con el usuario.
     * @return void No retorna un valor.
     */
    public function handleLogin(Login $evento): void
    {
        $this->registrar($evento->user, Auditoria::INICIO_SESION, 'Inicio de sesión en el sistema.');
    }

    /**
     * Se ejecuta cuando un usuario cierra su sesión.
     *
     * @param  Logout  $evento  Evento de cierre de sesión con el usuario.
     * @return void No retorna un valor.
     */
    public function handleLogout(Logout $evento): void
    {
        $this->registrar($evento->user, Auditoria::CIERRE_SESION, 'Cierre de sesión del sistema.');
    }

    /**
     * Guarda el registro en la auditoría.
     *
     * @param  Authenticatable|null  $usuario  Usuario que inició o cerró sesión.
     * @param  string  $accion  Acción realizada.
     * @param  string  $descripcion  Texto que se muestra en la auditoría.
     * @return void No retorna un valor.
     */
    private function registrar(?Authenticatable $usuario, string $accion, string $descripcion): void
    {
        // Al cerrar una sesión que ya había expirado, el evento puede llegar sin usuario
        if ($usuario === null) {
            return;
        }

        Auditoria::query()->create([
            'usuario_id' => $usuario->getAuthIdentifier(),
            'accion' => $accion,
            'tabla_afectada' => 'users',
            'registro_id' => $usuario->getAuthIdentifier(),
            'fecha_accion' => now(),
            'descripcion' => $descripcion,
            'ip' => request()?->ip(),
        ]);
    }
}
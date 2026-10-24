<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Restringe una acción a usuarios con el rol de veterinario.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
trait RequiereVeterinario
{
    /**
     * Detiene la petición con un error 403 si el usuario no es veterinario.
     *
     * @param  Request  $request  Petición del usuario autenticado.
     * @return void No retorna un valor.
     */
    protected function asegurarVeterinario(Request $request): void
    {
        abort_unless(
            $request->user()?->role?->nombre === 'Veterinario',
            403,
            'Solo los veterinarios pueden registrar información clínica.',
        );
    }
}
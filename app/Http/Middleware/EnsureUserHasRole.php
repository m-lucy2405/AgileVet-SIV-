<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limita el acceso a usuarios que tengan uno de los roles permitidos.
 */
class EnsureUserHasRole
{
    /**
     * Permite continuar únicamente si el usuario tiene un rol autorizado.
     *
     * @param  Request  $request  Solicitud HTTP actual.
     * @param  Closure  $next  Siguiente middleware de la solicitud.
     * @param  string  ...$roles  Nombres de roles autorizados.
     * @return Response Respuesta HTTP de la solicitud.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! in_array($user->role?->nombre, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permiso para acceder a este recurso.');
        }

        return $next($request);
    }
}

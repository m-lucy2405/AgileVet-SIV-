<?php

namespace App\Http\Controllers;

use App\Models\Mascota;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Muestra la lista de expedientes clínicos y el expediente de cada mascota.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class ExpedienteController extends Controller
{
    /**
     * Lista las mascotas con su propietario y el estado de su expediente.
     *
     * @param  Request  $request  Petición con el texto de búsqueda opcional.
     * @return View Vista con la lista paginada de mascotas.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $buscar = trim($filtros['buscar'] ?? '');

        $mascotas = Mascota::query()
            ->with(['cliente', 'expediente'])
            ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                $query->where(function (Builder $query) use ($buscar): void {
                    $query->where('nombre', 'like', "%{$buscar}%")
                        ->orWhereHas('cliente', function (Builder $query) use ($buscar): void {
                            $query->where('nombre', 'like', "%{$buscar}%")
                                ->orWhere('telefono', 'like', "%{$buscar}%");
                        });
                });
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('expedientes.index', [
            'mascotas' => $mascotas,
            'buscar' => $buscar,
        ]);
    }

    /**
     * Muestra el expediente completo de una mascota.
     *
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return View Vista con los datos clínicos y el historial de la mascota.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function show(Mascota $mascota): View
    {
        $mascota->load([
            'cliente',
            'expediente.consultas.veterinario',
            'expediente.consultas.tratamientos',
            'expediente.vacunaciones',
            'expediente.desparasitaciones',
        ]);

        $proximasCitas = $mascota->citas()
            ->with('veterinario')
            ->where('estado', 'pendiente')
            ->where('fecha_cita', '>=', now())
            ->orderBy('fecha_cita')
            ->get();

        return view('expedientes.show', [
            'mascota' => $mascota,
            'expediente' => $mascota->expediente,
            'proximasCitas' => $proximasCitas,
        ]);
    }
}
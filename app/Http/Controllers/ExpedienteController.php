<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiereVeterinario;
use App\Models\Mascota;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Muestra la lista de expedientes clínicos, el expediente de cada mascota y su
 * historial de consultas, y permite a los veterinarios actualizar sus datos generales.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class ExpedienteController extends Controller
{
    use RequiereVeterinario;

    /**
     * Consultas recientes que se muestran en el expediente.
     */
    private const CONSULTAS_RECIENTES = 1;

    /**
     * Consultas por página en el historial completo.
     */
    private const CONSULTAS_POR_PAGINA = 10;

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
     * Muestra el expediente de una mascota con sus consultas más recientes.
     *
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return View Vista con los datos clínicos y el historial reciente de la mascota.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function show(Mascota $mascota): View
    {
        $mascota->load([
            'cliente',
            'expediente.vacunaciones',
            'expediente.desparasitaciones',
        ]);

        $expediente = $mascota->expediente;

        $consultas = $expediente
            ? $expediente->consultas()
                ->with(['veterinario', 'tratamientos'])
                ->limit(self::CONSULTAS_RECIENTES)
                ->get()
            : collect();

        $proximasCitas = $mascota->citas()
            ->with('veterinario')
            ->where('estado', 'pendiente')
            ->where('fecha_cita', '>=', now())
            ->orderBy('fecha_cita')
            ->get();

        return view('expedientes.show', [
            'mascota' => $mascota,
            'expediente' => $expediente,
            'consultas' => $consultas,
            'totalConsultas' => $expediente?->consultas()->count() ?? 0,
            'proximasCitas' => $proximasCitas,
        ]);
    }

    /**
     * Muestra el historial completo de consultas de una mascota, con búsqueda por
     * motivo o diagnóstico. Disponible para cualquier usuario con sesión.
     *
     * @param  Request  $request  Petición con el texto de búsqueda opcional.
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return View Vista con el historial paginado.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function consultas(Request $request, Mascota $mascota): View
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $buscar = trim($filtros['buscar'] ?? '');

        $mascota->load(['cliente', 'expediente']);
        $expediente = $mascota->expediente;

        $consultas = $expediente
            ? $expediente->consultas()
                ->with(['veterinario', 'tratamientos'])
                ->when($buscar !== '', function (Builder $query) use ($buscar): void {
                    $query->where(function (Builder $query) use ($buscar): void {
                        $query->where('motivo', 'like', "%{$buscar}%")
                            ->orWhere('diagnostico', 'like', "%{$buscar}%");
                    });
                })
                ->paginate(self::CONSULTAS_POR_PAGINA)
                ->withQueryString()
            : null;

        return view('expedientes.consultas', [
            'mascota' => $mascota,
            'expediente' => $expediente,
            'consultas' => $consultas,
            'buscar' => $buscar,
        ]);
    }

    /**
     * Muestra el formulario para editar los datos generales del expediente.
     *
     * @param  Request  $request  Petición del usuario autenticado.
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return View Formulario con los datos actuales del expediente.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function edit(Request $request, Mascota $mascota): View
    {
        $this->asegurarVeterinario($request);

        $mascota->load(['cliente', 'expediente']);

        return view('expedientes.edit', [
            'mascota' => $mascota,
            'expediente' => $mascota->expediente,
        ]);
    }

    /**
     * Actualiza los datos generales del expediente; lo abre si todavía no existe.
     *
     * @param  Request  $request  Petición con antecedentes, alergias, enfermedades y observaciones.
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return RedirectResponse Redirección al expediente con el resultado.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function update(Request $request, Mascota $mascota): RedirectResponse
    {
        $this->asegurarVeterinario($request);

        $validated = $request->validate([
            'antecedentes' => ['nullable', 'string', 'max:5000'],
            'alergias' => ['nullable', 'string', 'max:5000'],
            'enfermedades_cronicas' => ['nullable', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ]);

        $abierto = DB::transaction(function () use ($mascota, $validated): bool {
            // Se bloquea la mascota para no abrir dos expedientes al mismo tiempo
            Mascota::query()->whereKey($mascota->getKey())->lockForUpdate()->firstOrFail();

            $expediente = $mascota->expediente()->firstOrCreate([], ['fecha_apertura' => now()]);
            $expediente->update($validated);

            return $expediente->wasRecentlyCreated;
        });

        return redirect()
            ->route('expedientes.show', $mascota)
            ->with('status', $abierto
                ? 'El expediente se abrió correctamente.'
                : 'Los datos del expediente se actualizaron correctamente.');
    }
}
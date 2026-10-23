<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiereVeterinario;
use App\Models\Mascota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Registra las desparasitaciones aplicadas en el expediente de una mascota.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class DesparasitacionController extends Controller
{
    use RequiereVeterinario;

    /**
     * Muestra el formulario para registrar una desparasitación.
     *
     * @param  Request  $request  Petición del usuario autenticado.
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return View Formulario de desparasitación.
     */
    public function create(Request $request, Mascota $mascota): View
    {
        $this->asegurarVeterinario($request);

        $mascota->load(['cliente', 'expediente']);

        return view('desparasitaciones.create', [
            'mascota' => $mascota,
            'expediente' => $mascota->expediente,
        ]);
    }

    /**
     * Valida y registra la desparasitación; abre el expediente si todavía no existe.
     *
     * @param  Request  $request  Petición con los datos de la desparasitación.
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return RedirectResponse Redirección al expediente con el resultado.
     */
    public function store(Request $request, Mascota $mascota): RedirectResponse
    {
        $this->asegurarVeterinario($request);

        $validated = $request->validate([
            'producto' => ['required', 'string', 'max:150'],
            'dosis' => ['required', 'string', 'max:100'],
            'fecha_aplicacion' => ['required', 'date', 'before_or_equal:today'],
            'proxima_aplicacion' => ['nullable', 'date', 'after:fecha_aplicacion'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ], [
            'producto.required' => 'Escribe el nombre del producto.',
            'dosis.required' => 'Escribe la dosis aplicada.',
            'fecha_aplicacion.required' => 'La fecha de aplicación es obligatoria.',
            'fecha_aplicacion.before_or_equal' => 'La fecha de aplicación no puede ser futura.',
            'proxima_aplicacion.after' => 'La próxima aplicación debe ser posterior a la fecha de aplicación.',
        ]);

        DB::transaction(function () use ($mascota, $validated): void {
            // Se bloquea la mascota para no abrir dos expedientes al mismo tiempo
            Mascota::query()->whereKey($mascota->getKey())->lockForUpdate()->firstOrFail();

            $expediente = $mascota->expediente()->firstOrCreate([], ['fecha_apertura' => now()]);
            $expediente->desparasitaciones()->create($validated);
        });

        return redirect()
            ->route('expedientes.show', $mascota)
            ->with('status', 'La desparasitación se registró correctamente.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiereVeterinario;
use App\Models\CatalogoDesparasitante;
use App\Models\Desparasitacion;
use App\Models\Mascota;
use App\Services\AgendaRefuerzoService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Registra las desparasitaciones aplicadas en el expediente de una mascota.
 * Con el catálogo sugiere la dosis, calcula la próxima aplicación y agenda la cita.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class DesparasitacionController extends Controller
{
    use RequiereVeterinario;

    /**
     * Muestra el formulario para registrar una desparasitación.
     */
    public function create(Request $request, Mascota $mascota): View
    {
        $this->asegurarVeterinario($request);

        $mascota->load(['cliente', 'expediente']);

        return view('desparasitaciones.create', [
            'mascota' => $mascota,
            'expediente' => $mascota->expediente,
            'catalogo' => CatalogoDesparasitante::query()->paraEspecie($mascota->especie)->get(),
        ]);
    }

    /**
     * Valida y registra la desparasitación; abre el expediente si todavía no existe
     * y, si se pidió, agenda la cita de la próxima aplicación.
     */
    public function store(Request $request, Mascota $mascota, AgendaRefuerzoService $agenda): RedirectResponse
    {
        $this->asegurarVeterinario($request);

        $validated = $request->validate([
            'catalogo_desparasitante_id' => ['nullable', 'integer', Rule::exists('catalogo_desparasitantes', 'id')->where('activo', true)],
            'producto' => ['required_without:catalogo_desparasitante_id', 'nullable', 'string', 'max:150'],
            'dosis' => ['nullable', 'string', 'max:100'],
            'fecha_aplicacion' => ['required', 'date', 'before_or_equal:today'],
            'proxima_aplicacion' => ['nullable', 'date', 'after:fecha_aplicacion'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'agendar_refuerzo' => ['nullable', 'boolean'],
        ], [
            'catalogo_desparasitante_id.exists' => 'Elige un producto válido del catálogo.',
            'producto.required_without' => 'Elige el producto del catálogo o escribe su nombre.',
            'fecha_aplicacion.required' => 'La fecha de aplicación es obligatoria.',
            'fecha_aplicacion.before_or_equal' => 'La fecha de aplicación no puede ser futura.',
            'proxima_aplicacion.after' => 'La próxima aplicación debe ser posterior a la fecha de aplicación.',
        ]);

        $catalogo = isset($validated['catalogo_desparasitante_id'])
            ? CatalogoDesparasitante::query()->findOrFail($validated['catalogo_desparasitante_id'])
            : null;

        // La dosis se puede escribir o tomar la sugerida del catálogo; al final es obligatoria
        $dosis = trim((string) ($validated['dosis'] ?? '')) ?: $catalogo?->dosis_sugerida;

        if (! $dosis) {
            throw ValidationException::withMessages(['dosis' => 'Escribe la dosis aplicada.']);
        }

        $datos = [
            'catalogo_desparasitante_id' => $catalogo?->id,
            'producto' => $catalogo?->nombre ?? $validated['producto'],
            'dosis' => $dosis,
            'fecha_aplicacion' => $validated['fecha_aplicacion'],
            'proxima_aplicacion' => $validated['proxima_aplicacion']
                ?? ($catalogo
                    ? $agenda->fechaRefuerzo(CarbonImmutable::parse($validated['fecha_aplicacion']), $catalogo->intervalo_dias)->toDateString()
                    : null),
            'observaciones' => $validated['observaciones'] ?? null,
        ];

        $desparasitacion = DB::transaction(function () use ($mascota, $datos): Desparasitacion {
            // Se bloquea la mascota para no abrir dos expedientes al mismo tiempo
            Mascota::query()->whereKey($mascota->getKey())->lockForUpdate()->firstOrFail();

            $expediente = $mascota->expediente()->firstOrCreate([], ['fecha_apertura' => now()]);

            return $expediente->desparasitaciones()->create($datos);
        });

        $mensaje = 'La desparasitación se registró correctamente.';

        // La cita se agenda después de guardar, con su propio bloqueo (veterinario y luego mascota)
        if ($request->boolean('agendar_refuerzo') && $desparasitacion->proxima_aplicacion) {
            $mensaje .= ' '.$this->agendarRefuerzo($agenda, $mascota, $request, $desparasitacion);
        }

        return redirect()
            ->route('expedientes.show', $mascota)
            ->with('status', $mensaje);
    }

    /**
     * Agenda la cita de la próxima aplicación y devuelve el texto para el mensaje.
     */
    private function agendarRefuerzo(AgendaRefuerzoService $agenda, Mascota $mascota, Request $request, Desparasitacion $desparasitacion): string
    {
        $fecha = CarbonImmutable::parse($desparasitacion->proxima_aplicacion->toDateString());

        if ($fecha->lte(CarbonImmutable::today())) {
            return 'La próxima aplicación ya pasó, así que no se agendó cita.';
        }

        $cita = $agenda->agendar($mascota, $request->user(), $fecha, "Desparasitación: {$desparasitacion->producto}");

        if ($cita === null) {
            return 'No se encontró horario libre para la próxima aplicación; agéndala manualmente.';
        }

        $desparasitacion->update(['cita_refuerzo_id' => $cita->id]);

        return 'Se agendó la próxima aplicación para '.$agenda->describir($cita).'.';
    }
}
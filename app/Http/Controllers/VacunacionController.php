<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiereVeterinario;
use App\Models\CatalogoVacuna;
use App\Models\Mascota;
use App\Models\Vacunacion;
use App\Services\AgendaRefuerzoService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Registra las vacunas aplicadas en el expediente de una mascota.
 * Con el catálogo calcula la próxima dosis y agenda automáticamente la cita de refuerzo.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class VacunacionController extends Controller
{
    use RequiereVeterinario;

    /**
     * Muestra el formulario para registrar una vacuna.
     */
    public function create(Request $request, Mascota $mascota): View
    {
        $this->asegurarVeterinario($request);

        $mascota->load(['cliente', 'expediente']);

        return view('vacunaciones.create', [
            'mascota' => $mascota,
            'expediente' => $mascota->expediente,
            'catalogo' => CatalogoVacuna::query()->paraEspecie($mascota->especie)->get(),
        ]);
    }

    /**
     * Valida y registra la vacuna; abre el expediente si todavía no existe
     * y, si se pidió, agenda la cita de refuerzo.
     */
    public function store(Request $request, Mascota $mascota, AgendaRefuerzoService $agenda): RedirectResponse
    {
        $this->asegurarVeterinario($request);

        $validated = $request->validate([
            'catalogo_vacuna_id' => ['nullable', 'integer', Rule::exists('catalogo_vacunas', 'id')->where('activo', true)],
            'nombre_vacuna' => ['required_without:catalogo_vacuna_id', 'nullable', 'string', 'max:150'],
            'fecha_aplicacion' => ['required', 'date', 'before_or_equal:today'],
            'proxima_dosis' => ['nullable', 'date', 'after:fecha_aplicacion'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'agendar_refuerzo' => ['nullable', 'boolean'],
        ], [
            'catalogo_vacuna_id.exists' => 'Elige una vacuna válida del catálogo.',
            'nombre_vacuna.required_without' => 'Elige la vacuna del catálogo o escribe su nombre.',
            'fecha_aplicacion.required' => 'La fecha de aplicación es obligatoria.',
            'fecha_aplicacion.before_or_equal' => 'La fecha de aplicación no puede ser futura.',
            'proxima_dosis.after' => 'La próxima dosis debe ser posterior a la fecha de aplicación.',
        ]);

        // Con catálogo: se copia el nombre y, si no la escribieron, se calcula la próxima dosis
        $catalogo = isset($validated['catalogo_vacuna_id'])
            ? CatalogoVacuna::query()->findOrFail($validated['catalogo_vacuna_id'])
            : null;

        $datos = [
            'catalogo_vacuna_id' => $catalogo?->id,
            'nombre_vacuna' => $catalogo?->nombre ?? $validated['nombre_vacuna'],
            'fecha_aplicacion' => $validated['fecha_aplicacion'],
            'proxima_dosis' => $validated['proxima_dosis']
                ?? ($catalogo
                    ? $agenda->fechaRefuerzo(CarbonImmutable::parse($validated['fecha_aplicacion']), $catalogo->intervalo_dias)->toDateString()
                    : null),
            'observaciones' => $validated['observaciones'] ?? null,
        ];

        $vacuna = DB::transaction(function () use ($mascota, $datos): Vacunacion {
            // Se bloquea la mascota para no abrir dos expedientes al mismo tiempo
            Mascota::query()->whereKey($mascota->getKey())->lockForUpdate()->firstOrFail();

            $expediente = $mascota->expediente()->firstOrCreate([], ['fecha_apertura' => now()]);

            return $expediente->vacunaciones()->create($datos);
        });

        $mensaje = 'La vacuna se registró correctamente.';

        // La cita se agenda después de guardar, con su propio bloqueo (veterinario y luego mascota)
        if ($request->boolean('agendar_refuerzo') && $vacuna->proxima_dosis) {
            $mensaje .= ' '.$this->agendarRefuerzo($agenda, $mascota, $request, $vacuna);
        }

        return redirect()
            ->route('expedientes.show', $mascota)
            ->with('status', $mensaje);
    }

    /**
     * Agenda la cita de refuerzo y devuelve el texto para el mensaje de resultado.
     */
    private function agendarRefuerzo(AgendaRefuerzoService $agenda, Mascota $mascota, Request $request, Vacunacion $vacuna): string
    {
        $fecha = CarbonImmutable::parse($vacuna->proxima_dosis->toDateString());

        if ($fecha->lte(CarbonImmutable::today())) {
            return 'La próxima dosis ya pasó, así que no se agendó cita.';
        }

        $cita = $agenda->agendar($mascota, $request->user(), $fecha, "Refuerzo de {$vacuna->nombre_vacuna}");

        if ($cita === null) {
            return 'No se encontró horario libre para el refuerzo; agéndalo manualmente.';
        }

        $vacuna->update(['cita_refuerzo_id' => $cita->id]);

        return 'Se agendó el refuerzo para '.$agenda->describir($cita).'.';
    }
}
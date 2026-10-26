<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RequiereVeterinario;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Mascota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Registra y corrige consultas en el expediente clínico de una mascota.
 * Solo los veterinarios pueden registrar consultas, y solo quien la registró
 * puede corregirla durante el mismo día.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class ConsultaController extends Controller
{
    use RequiereVeterinario;

    /**
     * Muestra el formulario para registrar una consulta.
     *
     * @param  Request  $request  Petición con la cita opcional (?cita=ID).
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return View Formulario de consulta.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function create(Request $request, Mascota $mascota): View
    {
        $this->asegurarVeterinario($request);

        $mascota->load(['cliente', 'expediente']);

        // Cita opcional: debe ser de esta mascota, estar pendiente y no tener consulta
        $cita = null;
        if ($request->filled('cita')) {
            $cita = $mascota->citas()
                ->whereKey((int) $request->query('cita'))
                ->where('estado', 'pendiente')
                ->whereDoesntHave('consulta')
                ->first();
        }

        return view('consultas.create', [
            'mascota' => $mascota,
            'expediente' => $mascota->expediente,
            'cita' => $cita,
        ]);
    }

    /**
     * Valida y registra la consulta; abre el expediente si todavía no existe
     * y marca como atendida la cita de origen.
     *
     * @param  Request  $request  Petición con los datos de la consulta y sus tratamientos.
     * @param  Mascota  $mascota  Mascota resuelta mediante route model binding.
     * @return RedirectResponse Redirección al expediente con el resultado.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function store(Request $request, Mascota $mascota): RedirectResponse
    {
        $this->asegurarVeterinario($request);
        $this->descartarFilasVacias($request);

        $validated = $request->validate([
            'cita_id' => [
                'nullable',
                'integer',
                Rule::exists('citas', 'id')
                    ->where('mascota_id', $mascota->id)
                    ->where('estado', 'pendiente'),
                Rule::unique('consultas', 'cita_id'),
            ],
            ...$this->reglasConsulta(),

            // Datos para abrir el expediente (solo se usan si la mascota aún no tiene)
            'antecedentes' => ['nullable', 'string', 'max:5000'],
            'alergias' => ['nullable', 'string', 'max:5000'],
            'enfermedades_cronicas' => ['nullable', 'string', 'max:5000'],
        ], $this->mensajes());

        DB::transaction(function () use ($request, $mascota, $validated): void {
            // Se bloquea la mascota para que dos consultas simultáneas no abran dos expedientes
            Mascota::query()->whereKey($mascota->getKey())->lockForUpdate()->firstOrFail();

            $expediente = $mascota->expediente()->firstOrCreate([], [
                'fecha_apertura' => now(),
                'antecedentes' => $validated['antecedentes'] ?? null,
                'alergias' => $validated['alergias'] ?? null,
                'enfermedades_cronicas' => $validated['enfermedades_cronicas'] ?? null,
            ]);

            $citaId = $validated['cita_id'] ?? null;

            if ($citaId !== null) {
                $cita = Cita::query()->whereKey($citaId)->lockForUpdate()->firstOrFail();

                if ($cita->estado !== 'pendiente') {
                    throw ValidationException::withMessages([
                        'cita_id' => 'La cita ya no está pendiente.',
                    ]);
                }

                $cita->update(['estado' => 'atendida']);
            }

            $consulta = Consulta::query()->create([
                'expediente_id' => $expediente->id,
                'veterinario_id' => $request->user()->id,
                'cita_id' => $citaId,
                'fecha_consulta' => now(),
                ...$this->datosConsulta($validated),
            ]);

            $consulta->tratamientos()->createMany($validated['tratamientos'] ?? []);
        });

        return redirect()
            ->route('expedientes.show', $mascota)
            ->with('status', 'La consulta se registró correctamente.');
    }

    /**
     * Muestra el formulario para corregir una consulta.
     *
     * @param  Request  $request  Petición del usuario autenticado.
     * @param  Mascota  $mascota  Mascota dueña del expediente.
     * @param  Consulta  $consulta  Consulta que se corregirá.
     * @return View Formulario con los datos actuales de la consulta.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function edit(Request $request, Mascota $mascota, Consulta $consulta): View
    {
        $this->asegurarCorreccion($request, $mascota, $consulta);

        $mascota->load(['cliente', 'expediente']);
        $consulta->load('tratamientos');

        return view('consultas.edit', [
            'mascota' => $mascota,
            'expediente' => $mascota->expediente,
            'consulta' => $consulta,
        ]);
    }

    /**
     * Guarda la corrección de una consulta y reemplaza sus medicamentos.
     *
     * @param  Request  $request  Petición con los datos corregidos.
     * @param  Mascota  $mascota  Mascota dueña del expediente.
     * @param  Consulta  $consulta  Consulta que se corregirá.
     * @return RedirectResponse Redirección al expediente con el resultado.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function update(Request $request, Mascota $mascota, Consulta $consulta): RedirectResponse
    {
        $this->asegurarCorreccion($request, $mascota, $consulta);
        $this->descartarFilasVacias($request);

        $validated = $request->validate($this->reglasConsulta(), $this->mensajes());

        DB::transaction(function () use ($consulta, $validated): void {
            $bloqueada = Consulta::query()->whereKey($consulta->getKey())->lockForUpdate()->firstOrFail();

            $bloqueada->update($this->datosConsulta($validated));

            // Los medicamentos se reemplazan por los que quedaron en el formulario
            $bloqueada->tratamientos()->delete();
            $bloqueada->tratamientos()->createMany($validated['tratamientos'] ?? []);

            // Marca la hora de corrección aunque solo hayan cambiado los medicamentos
            $bloqueada->touch();
        });

        return redirect()
            ->route('expedientes.show', $mascota)
            ->with('status', 'La consulta se corrigió correctamente.');
    }

    /**
     * Verifica que la consulta sea de esta mascota y que el usuario pueda corregirla.
     *
     * @param  Request  $request  Petición del usuario autenticado.
     * @param  Mascota  $mascota  Mascota indicada en la dirección.
     * @param  Consulta  $consulta  Consulta indicada en la dirección.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function asegurarCorreccion(Request $request, Mascota $mascota, Consulta $consulta): void
    {
        $this->asegurarVeterinario($request);

        // La consulta debe pertenecer al expediente de la mascota de la dirección
        abort_unless((int) $consulta->expediente?->mascota_id === (int) $mascota->id, 404);

        abort_unless(
            $consulta->puedeSerCorregidaPor($request->user()),
            403,
            'Solo el veterinario que registró la consulta puede corregirla, y únicamente el mismo día.',
        );
    }

    /**
     * Quita las filas de medicamentos que quedaron completamente vacías.
     *
     * @param  Request  $request  Petición con la lista de tratamientos.
     * @return void No retorna un valor.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function descartarFilasVacias(Request $request): void
    {
        $request->merge([
            'tratamientos' => collect($request->input('tratamientos', []))
                ->filter(fn ($fila): bool => is_array($fila) && collect($fila)->filter(fn ($valor) => filled($valor))->isNotEmpty())
                ->values()
                ->all(),
        ]);
    }

    /**
     * Reglas comunes de los datos clínicos de una consulta y sus medicamentos.
     *
     * @return array<string, array<int, string>> Reglas de validación.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function reglasConsulta(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:255'],
            'peso_kg' => ['nullable', 'numeric', 'between:0.01,2000'],
            'temperatura_c' => ['nullable', 'numeric', 'between:30,45'],
            'diagnostico' => ['required', 'string', 'max:5000'],
            'tratamiento_indicado' => ['nullable', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],

            'tratamientos' => ['nullable', 'array', 'max:20'],
            'tratamientos.*.medicamento' => ['required', 'string', 'max:150'],
            'tratamientos.*.dosis' => ['required', 'string', 'max:100'],
            'tratamientos.*.frecuencia' => ['required', 'string', 'max:100'],
            'tratamientos.*.duracion' => ['required', 'string', 'max:100'],
            'tratamientos.*.indicaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Mensajes de validación en español.
     *
     * @return array<string, string> Mensajes por regla.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function mensajes(): array
    {
        return [
            'diagnostico.required' => 'El diagnóstico es obligatorio.',
            'motivo.required' => 'El motivo de la consulta es obligatorio.',
            'peso_kg.between' => 'El peso debe estar entre 0.01 y 2000 kg.',
            'temperatura_c.between' => 'La temperatura debe estar entre 30 y 45 °C.',
            'cita_id.exists' => 'La cita ya no está pendiente o no pertenece a esta mascota.',
            'cita_id.unique' => 'Esta cita ya tiene una consulta registrada.',
            'tratamientos.*.medicamento.required' => 'Escribe el nombre del medicamento.',
            'tratamientos.*.dosis.required' => 'Escribe la dosis.',
            'tratamientos.*.frecuencia.required' => 'Escribe la frecuencia.',
            'tratamientos.*.duracion.required' => 'Escribe la duración.',
        ];
    }

    /**
     * Extrae de los datos validados las columnas clínicas de la consulta.
     *
     * @param  array<string, mixed>  $validated  Datos validados.
     * @return array<string, mixed> Columnas listas para guardar.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    private function datosConsulta(array $validated): array
    {
        return [
            'motivo' => $validated['motivo'],
            'peso_kg' => $validated['peso_kg'] ?? null,
            'temperatura_c' => $validated['temperatura_c'] ?? null,
            'diagnostico' => $validated['diagnostico'],
            'tratamiento_indicado' => $validated['tratamiento_indicado'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
        ];
    }
}
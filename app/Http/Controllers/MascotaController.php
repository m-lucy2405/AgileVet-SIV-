<?php

namespace App\Http\Controllers;

use App\Models\Mascota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Edita los datos de las mascotas registradas.
 */
class MascotaController extends Controller
{
    private const ESPECIES = ['Canino', 'Felino', 'Ave', 'Bovino', 'Porcino'];

    /**
     * Muestra el formulario para editar una mascota.
     */
    public function edit(Mascota $mascota): View
    {
        return view('mascotas.edit', [
            'mascota' => $mascota->load('cliente'),
            'especies' => self::ESPECIES,
        ]);
    }

    /**
     * Actualiza los datos de la mascota.
     */
    public function update(Request $request, Mascota $mascota): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'especie' => ['required', 'string', Rule::in(self::ESPECIES)],
            'raza' => ['nullable', 'string', 'max:100'],
            'sexo' => ['required', 'string', Rule::in(['macho', 'hembra'])],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'color' => ['nullable', 'string', 'max:50'],
            'esterilizado' => ['sometimes', 'boolean'],
        ]);

        $mascota->update([...$datos, 'esterilizado' => $request->boolean('esterilizado')]);

        return redirect()
            ->route('clientes.show', $mascota->cliente_id)
            ->with('success', 'Los datos de la mascota se actualizaron correctamente.');
    }
}
{{-- Una fila de medicamento. $i es el índice de la fila y $fila sus datos previos --}}
<div class="rounded-lg border border-gray-200 p-4" data-medicamento>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'medicamento' => ['Medicamento', 150, 'Ej. Amoxicilina 250 mg'],
            'dosis' => ['Dosis', 100, 'Ej. 1 tableta'],
            'frecuencia' => ['Frecuencia', 100, 'Ej. cada 12 horas'],
            'duracion' => ['Duración', 100, 'Ej. 7 días'],
        ] as $campo => [$etiqueta, $largo, $ejemplo])
            <div>
                <label for="tratamiento-{{ $i }}-{{ $campo }}" class="etiqueta">{{ $etiqueta }}</label>
                <input id="tratamiento-{{ $i }}-{{ $campo }}" name="tratamientos[{{ $i }}][{{ $campo }}]" type="text"
                    maxlength="{{ $largo }}" placeholder="{{ $ejemplo }}" class="campo"
                    value="{{ $fila[$campo] ?? '' }}">
                @error("tratamientos.$i.$campo")
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>

    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="tratamiento-{{ $i }}-indicaciones" class="etiqueta">Indicaciones</label>
            <input id="tratamiento-{{ $i }}-indicaciones" name="tratamientos[{{ $i }}][indicaciones]" type="text"
                maxlength="5000" placeholder="Opcional. Ej. dar con comida" class="campo"
                value="{{ $fila['indicaciones'] ?? '' }}">
        </div>
        <button type="button" data-quitar-medicamento
            class="rounded-lg px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
            Quitar
        </button>
    </div>
</div>
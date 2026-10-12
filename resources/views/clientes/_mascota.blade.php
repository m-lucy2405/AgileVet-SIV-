@php
    $especieElegida = old("mascotas.$indice.especie_id");
    $razas = $razasPorEspecie[$especieElegida] ?? [];
    $prefijo = "mascota-$indice";
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" data-mascota>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-800" data-titulo-mascota>Mascota</h3>
        <button type="button" data-quitar-mascota
            class="hidden rounded-md px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
            Quitar
        </button>
    </div>

    <div class="grid gap-5 md:grid-cols-[8rem_1fr]">

        {{-- Foto --}}
        <div>
            <div data-vista-foto
                class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 text-xs text-gray-400">
                Sin foto
            </div>
            <label for="{{ $prefijo }}-foto"
                class="mt-2 inline-block cursor-pointer text-xs font-medium text-primario-700 hover:underline">
                Subir foto
            </label>
            <input id="{{ $prefijo }}-foto" name="mascotas[{{ $indice }}][foto]" type="file"
                accept="image/*" class="sr-only" data-foto>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            <div>
                <label for="{{ $prefijo }}-nombre" class="etiqueta">Nombre <span class="text-red-500">*</span></label>
                <input id="{{ $prefijo }}-nombre" name="mascotas[{{ $indice }}][nombre]" type="text"
                    required maxlength="100" class="campo" value="{{ old("mascotas.$indice.nombre") }}">
                @error("mascotas.$indice.nombre")
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefijo }}-especie" class="etiqueta">Especie <span class="text-red-500">*</span></label>
                <select id="{{ $prefijo }}-especie" name="mascotas[{{ $indice }}][especie_id]"
                    required class="campo" data-especie>
                    <option value="">Selecciona</option>
                    @foreach ($especies as $id => $nombreEspecie)
                        <option value="{{ $id }}" @selected($especieElegida == $id)>{{ $nombreEspecie }}</option>
                    @endforeach
                </select>
                @error("mascotas.$indice.especie_id")
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

        
    </div>
</div>
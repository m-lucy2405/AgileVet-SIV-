@php
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
                  <select id="{{ $prefijo }}-especie" name="mascotas[{{ $indice }}][especie]"
                required class="campo" data-especie>
                <option value="">Selecciona</option>
                @foreach ($especies as $especie)
                    <option value="{{ $especie }}" @selected(old("mascotas.$indice.especie") === $especie)>{{ $especie }}</option>
                @endforeach
            </select>
            @error("mascotas.$indice.especie")
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefijo }}-raza" class="etiqueta">Raza</label>
               <input id="{{ $prefijo }}-raza" name="mascotas[{{ $indice }}][raza]" type="text"
                maxlength="100" list="{{ $prefijo }}-lista-razas" placeholder="Ej. Mestizo"
                class="campo" data-raza value="{{ old("mascotas.$indice.raza") }}">
            <datalist id="{{ $prefijo }}-lista-razas">
                @foreach ($razasPorEspecie[old("mascotas.$indice.especie")] ?? [] as $raza)
                    <option value="{{ $raza }}">
                @endforeach
            </datalist>
            </div>

            <div>
                <label for="{{ $prefijo }}-sexo" class="etiqueta">Sexo <span class="text-red-500">*</span></label>
                <select id="{{ $prefijo }}-sexo" name="mascotas[{{ $indice }}][sexo]" required class="campo">
                    <option value="">Selecciona</option>
                    <option value="macho" @selected(old("mascotas.$indice.sexo") === 'macho')>Macho</option>
                    <option value="hembra" @selected(old("mascotas.$indice.sexo") === 'hembra')>Hembra</option>
                </select>
                @error("mascotas.$indice.sexo")
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefijo }}-fecha" class="etiqueta">Fecha de nacimiento</label>
                <input id="{{ $prefijo }}-fecha" name="mascotas[{{ $indice }}][fecha_nacimiento]" type="date"
                    max="{{ date('Y-m-d') }}" class="campo" data-fecha-nacimiento
                    value="{{ old("mascotas.$indice.fecha_nacimiento") }}">
                <p class="mt-1 text-xs text-gray-500" data-edad>Si no la conoce, use una fecha aproximada.</p>
            </div>

            <div>
                <label for="{{ $prefijo }}-color" class="etiqueta">Color</label>
                <input id="{{ $prefijo }}-color" name="mascotas[{{ $indice }}][color]" type="text"
                    maxlength="50" placeholder="Ej. Café con blanco" class="campo"
                    value="{{ old("mascotas.$indice.color") }}">
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                    <input type="hidden" name="mascotas[{{ $indice }}][esterilizado]" value="0">
                    <input type="checkbox" name="mascotas[{{ $indice }}][esterilizado]" value="1"
                        class="h-4 w-4 rounded border-gray-300 accent-primario-600"
                        @checked(old("mascotas.$indice.esterilizado"))>
                    Esterilizado(a)
                </label>
            </div>
        </div>
</div>
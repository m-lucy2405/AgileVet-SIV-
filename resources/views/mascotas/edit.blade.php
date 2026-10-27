@extends('layouts.app')

@section('titulo', 'Editar mascota')

@section('contenido')
    <form method="POST" action="{{ route('mascotas.update', $mascota) }}"
        class="mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <p class="mb-5 text-sm text-gray-500">Propietario: {{ $mascota->cliente->nombre }}</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="nombre" class="etiqueta">Nombre <span class="text-red-500">*</span></label>
                <input id="nombre" name="nombre" type="text" required maxlength="100" class="campo"
                    value="{{ old('nombre', $mascota->nombre) }}">
                @error('nombre') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="especie" class="etiqueta">Especie <span class="text-red-500">*</span></label>
                <select id="especie" name="especie" required class="campo">
                    @foreach ($especies as $especie)
                        <option value="{{ $especie }}" @selected(old('especie', $mascota->especie) === $especie)>{{ $especie }}</option>
                    @endforeach
                </select>
                @error('especie') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="raza" class="etiqueta">Raza</label>
                <input id="raza" name="raza" type="text" maxlength="100" class="campo"
                    value="{{ old('raza', $mascota->raza) }}">
                @error('raza') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="sexo" class="etiqueta">Sexo <span class="text-red-500">*</span></label>
                <select id="sexo" name="sexo" required class="campo">
                    <option value="macho" @selected(old('sexo', $mascota->sexo) === 'macho')>Macho</option>
                    <option value="hembra" @selected(old('sexo', $mascota->sexo) === 'hembra')>Hembra</option>
                </select>
                @error('sexo') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="fecha_nacimiento" class="etiqueta">Fecha de nacimiento</label>
                <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" max="{{ date('Y-m-d') }}" class="campo"
                    value="{{ old('fecha_nacimiento', $mascota->fecha_nacimiento?->format('Y-m-d')) }}">
                @error('fecha_nacimiento') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="color" class="etiqueta">Color</label>
                <input id="color" name="color" type="text" maxlength="50" class="campo"
                    value="{{ old('color', $mascota->color) }}">
                @error('color') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="hidden" name="esterilizado" value="0">
                <input type="checkbox" name="esterilizado" value="1" class="h-4 w-4 rounded border-gray-300"
                    @checked(old('esterilizado', $mascota->esterilizado))>
                Esterilizado(a)
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
            <a href="{{ route('clientes.show', $mascota->cliente_id) }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Guardar cambios</button>
        </div>
    </form>
@endsection
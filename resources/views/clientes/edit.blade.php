@extends('layouts.app')

@section('titulo', 'Editar cliente')

@section('contenido')
    <form method="POST" action="{{ route('clientes.update', $cliente) }}"
        class="mx-auto max-w-3xl space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div>
            <label for="nombre" class="etiqueta">Nombre completo <span class="text-red-500">*</span></label>
            <input id="nombre" name="nombre" type="text" required maxlength="150" class="campo"
                value="{{ old('nombre', $cliente->nombre) }}">
            @error('nombre') <p class="error-campo">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="telefono" class="etiqueta">Teléfono <span class="text-red-500">*</span></label>
                <input id="telefono" name="telefono" type="tel" required maxlength="20" placeholder="7000-0000"
                    class="campo" value="{{ old('telefono', $cliente->telefono) }}">
                @error('telefono') <p class="error-campo">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="etiqueta">Correo electrónico</label>
                <input id="email" name="email" type="email" maxlength="150" class="campo"
                    value="{{ old('email', $cliente->email) }}">
                @error('email') <p class="error-campo">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="direccion" class="etiqueta">Dirección <span class="text-red-500">*</span></label>
            <input id="direccion" name="direccion" type="text" required maxlength="255" class="campo"
                value="{{ old('direccion', $cliente->direccion) }}">
            @error('direccion') <p class="error-campo">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
            <a href="{{ route('clientes.show', $cliente) }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Guardar cambios</button>
        </div>
    </form>
@endsection
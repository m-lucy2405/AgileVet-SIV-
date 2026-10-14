@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
    @php
        // Datos de ejemplo para la maquetación y prueba ahora que no existe el controlador
        $usuario = [
            'id' => 2,
            'nombre' => 'María López',
            'email' => 'maria.lopez@lamascota.com',
            'rol_id' => 2,
            'estado' => 1,
        ];
    @endphp

    <div class="mx-auto max-w-3xl">
        <a href="{{ route('usuarios.index') }}" class="mb-4 inline-block text-sm text-primario-700 hover:underline">
            &larr; Volver a usuarios
        </a>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Editar datos del usuario</h2>
            <p class="mt-1 text-sm text-gray-500">Modifica la información y guarda los cambios.</p>

            <form method="POST" action="#" class="mt-6">
                @csrf
                @method('PUT')

                @include('usuarios._formulario', ['usuario' => $usuario])

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('usuarios.index') }}" class="btn-secundario">Cancelar</a>
                    <button type="submit" class="btn-primario">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
@endsection
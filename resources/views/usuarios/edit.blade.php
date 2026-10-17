@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('usuarios.index') }}" class="mb-4 inline-block text-sm text-primario-700 hover:underline">
            &larr; Volver a usuarios
        </a>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Editar datos del usuario</h2>
            <p class="mt-1 text-sm text-gray-500">Modifica la información y guarda los cambios.</p>

            <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="mt-6">
                @csrf
                @method('PUT')

                @include('usuarios._formulario', ['usuario' => $usuario, 'roles' => $roles])

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('usuarios.index') }}" class="btn-secundario">Cancelar</a>
                    <button type="submit" class="btn-primario">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
@endsection
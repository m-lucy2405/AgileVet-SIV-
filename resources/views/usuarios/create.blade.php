@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('usuarios.index') }}" class="mb-4 inline-block text-sm text-primario-700 hover:underline">
            &larr; Volver a usuarios
        </a>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Datos del usuario</h2>
            <p class="mt-1 text-sm text-gray-500">Completa la información para registrar un nuevo usuario.</p>

            <form method="POST" action="#" class="mt-6">
                @csrf

                @include('usuarios._formulario', ['usuario' => null])

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('usuarios.index') }}" class="btn-secundario">Cancelar</a>
                    <button type="submit" class="btn-primario">Guardar usuario</button>
                </div>
            </form>
        </div>
    </div>
@endsection
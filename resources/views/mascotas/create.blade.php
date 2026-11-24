@extends('layouts.app')

@section('titulo', 'Agregar mascota')

@section('contenido')
    <div class="mx-auto mb-4 max-w-3xl">
        <a href="{{ route('clientes.show', $cliente) }}" class="btn-volver">&larr; Volver a la ficha</a>
    </div>
    <form method="POST" action="{{ route('mascotas.store', $cliente) }}"
        class="mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf

        <p class="mb-5 text-sm text-gray-500">Propietario: {{ $cliente->nombre }}</p>

        @include('mascotas._campos', ['mascota' => null])

        <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
            <a href="{{ route('clientes.show', $cliente) }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Guardar mascota</button>
        </div>
    </form>
@endsection
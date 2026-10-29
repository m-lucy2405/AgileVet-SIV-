@extends('layouts.app')

@section('titulo', 'Editar producto')

@section('contenido')
    <a href="{{ route('productos.index') }}" class="mb-4 btn-volver">
        &larr; Volver al inventario
    </a>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudieron guardar los cambios:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- El stock no se edita aquí: cambia con entradas, salidas y ajustes --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-gray-500">Stock actual</p>
        <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $producto->stock }} {{ $producto->unidad }}</p>
        <p class="mt-1 text-xs text-gray-500">
            El stock no se modifica desde este formulario: se cambia registrando entradas, salidas o ajustes.
        </p>
    </section>

    <p class="mb-4 text-sm text-gray-600">Los campos con <span class="text-red-500">*</span> son obligatorios.</p>

    <form method="POST" action="{{ route('productos.update', $producto) }}"
        class="space-y-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PUT')

        @include('inventario._formulario', ['producto' => $producto])

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('productos.index') }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Guardar cambios</button>
        </div>
    </form>
@endsection
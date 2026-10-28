@extends('layouts.app')

@section('titulo', 'Nuevo producto')

@section('contenido')
    <a href="{{ route('productos.index') }}" class="mb-4 inline-block text-sm text-primario-300 hover:underline">
        &larr; Volver al inventario
    </a>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudo guardar el producto:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mb-4 text-sm text-gray-600">Los campos con <span class="text-red-500">*</span> son obligatorios.</p>

    <form method="POST" action="{{ route('productos.store') }}"
        class="space-y-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf

        @include('inventario._formulario')

        <div class="border-t border-gray-100 pt-4 sm:max-w-xs">
            <label for="stock_inicial" class="etiqueta">Stock inicial</label>
            <input id="stock_inicial" name="stock_inicial" type="number" step="1" min="0" class="campo"
                value="{{ old('stock_inicial', 0) }}">
            <p class="mt-1 text-xs text-gray-500">Quedará registrado como la primera entrada del kárdex.</p>
            @error('stock_inicial')
                <p class="error-campo">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-4 border-t border-gray-100 pt-4 sm:grid-cols-2">
            <div>
                <label for="codigo_lote_inicial" class="etiqueta">Código del lote inicial</label>
                <input id="codigo_lote_inicial" name="codigo_lote_inicial" type="text" maxlength="100"
                    class="campo" value="{{ old('codigo_lote_inicial') }}">
                <p class="mt-1 text-xs text-gray-500">Si se deja vacío, el sistema genera un código.</p>
                @error('codigo_lote_inicial')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('productos.index') }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Guardar producto</button>
        </div>
    </form>
@endsection
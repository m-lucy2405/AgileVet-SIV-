@extends('layouts.app')

@section('titulo', 'Registrar desparasitación')

@section('contenido')
    <a href="{{ route('expedientes.show', $mascota) }}" class="mb-4 inline-block text-sm text-primario-300 hover:underline">
        &larr; Volver al expediente
    </a>

    {{-- Mascota --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-gray-800">{{ $mascota->nombre }}</h2>
        <p class="mt-1 text-sm text-gray-500">
            {{ $mascota->especie }} · Propietario: {{ $mascota->cliente->nombre }} · {{ $mascota->cliente->telefono }}
        </p>
    </section>

    {{-- Alergias: visibles antes de aplicar --}}
    @if ($expediente?->alergias)
        <div role="alert" class="mb-6 rounded-xl border border-red-500/40 bg-red-50 px-5 py-4 text-sm text-red-600">
            <p class="font-semibold uppercase tracking-wide">Alergias</p>
            <p class="mt-1 whitespace-pre-line">{{ $expediente->alergias }}</p>
        </div>
    @endif

    {{-- Errores del servidor --}}
    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudo guardar la desparasitación:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mb-4 text-sm text-gray-600">Los campos con <span class="text-red-500">*</span> son obligatorios.</p>

    <form method="POST" action="{{ route('desparasitaciones.store', $mascota) }}"
        class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="producto" class="etiqueta">Producto <span class="text-red-500">*</span></label>
                <input id="producto" name="producto" type="text" required maxlength="150"
                    placeholder="Ej. Albendazol" class="campo" value="{{ old('producto') }}">
                @error('producto')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="dosis" class="etiqueta">Dosis <span class="text-red-500">*</span></label>
                <input id="dosis" name="dosis" type="text" required maxlength="100"
                    placeholder="Ej. 1 tableta" class="campo" value="{{ old('dosis') }}">
                @error('dosis')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_aplicacion" class="etiqueta">Fecha de aplicación <span class="text-red-500">*</span></label>
                <input id="fecha_aplicacion" name="fecha_aplicacion" type="date" required max="{{ now()->toDateString() }}"
                    class="campo" value="{{ old('fecha_aplicacion', now()->toDateString()) }}">
                @error('fecha_aplicacion')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="proxima_aplicacion" class="etiqueta">Próxima aplicación</label>
                <input id="proxima_aplicacion" name="proxima_aplicacion" type="date" min="{{ now()->addDay()->toDateString() }}"
                    class="campo" value="{{ old('proxima_aplicacion') }}">
                @error('proxima_aplicacion')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="observaciones" class="etiqueta">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="3" maxlength="5000"
                class="campo">{{ old('observaciones') }}</textarea>
            @error('observaciones')
                <p class="error-campo">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('expedientes.show', $mascota) }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Guardar desparasitación</button>
        </div>
    </form>
@endsection
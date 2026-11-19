@extends('layouts.app')

@section('titulo', 'Registrar desparasitación')

@section('contenido')
    <a href="{{ route('expedientes.show', $mascota) }}" class="mb-4 btn-volver">
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

    <form method="POST" action="{{ route('desparasitaciones.store', $mascota) }}" id="form-aplicacion"
        class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="catalogo_id" class="etiqueta">Producto <span class="text-red-500">*</span></label>
                <select id="catalogo_id" name="catalogo_desparasitante_id" class="campo">
                    @foreach ($catalogo as $producto)
                        <option value="{{ $producto->id }}" data-intervalo="{{ $producto->intervalo_dias }}"
                            data-dosis="{{ $producto->dosis_sugerida }}"
                            @selected(old('catalogo_desparasitante_id') == $producto->id)>
                            {{ $producto->nombre }} (cada {{ $producto->intervalo_dias }} días)
                        </option>
                    @endforeach
                    <option value="" @selected(old('catalogo_desparasitante_id') === '' || $catalogo->isEmpty())>Otro (escribir el nombre)</option>
                </select>
                @error('catalogo_desparasitante_id')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

            <div id="bloque-nombre">
                <label for="nombre_libre" class="etiqueta">Nombre del producto <span class="text-red-500">*</span></label>
                <input id="nombre_libre" name="producto" type="text" maxlength="150"
                    placeholder="Ej. Albendazol" class="campo" value="{{ old('producto') }}">
                <p class="mt-1 text-xs text-gray-500">Solo si el producto no está en la lista.</p>
                @error('producto')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="dosis" class="etiqueta">Dosis <span class="text-red-500">*</span></label>
                <input id="dosis" name="dosis" type="text" maxlength="100"
                    placeholder="Ej. 1 tableta" class="campo" value="{{ old('dosis') }}">
                <p class="mt-1 text-xs text-gray-500">Se llena con la dosis sugerida; puedes cambiarla.</p>
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
                <label for="proxima" class="etiqueta">Próxima aplicación</label>
                <input id="proxima" name="proxima_aplicacion" type="date" min="{{ now()->addDay()->toDateString() }}"
                    class="campo" value="{{ old('proxima_aplicacion') }}">
                <p id="ayuda-proxima" class="mt-1 text-xs text-gray-500"></p>
                @error('proxima_aplicacion')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @include('vacunaciones._agendar_refuerzo')

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

@push('scripts')
    @include('vacunaciones._script_refuerzo')
@endpush
@extends('layouts.app')

@section('titulo', $expediente ? 'Editar expediente' : 'Abrir expediente')

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
        @if ($expediente)
            <p class="mt-1 text-xs text-gray-500">Expediente abierto el {{ $expediente->fecha_apertura->format('d/m/Y') }}</p>
        @else
            <p class="mt-3 text-sm text-gray-700">
                Esta mascota todavía no tiene expediente. Al guardar, se abrirá con estos datos.
            </p>
        @endif
    </section>

    {{-- Errores del servidor --}}
    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudieron guardar los datos:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('expedientes.update', $mascota) }}"
        class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PUT')

        <p class="text-sm text-gray-600">Todos los campos son opcionales. Déjalos vacíos si no hay registros.</p>

        @foreach ([
            'alergias' => ['Alergias', 'Ej. Penicilina, picadura de abeja'],
            'antecedentes' => ['Antecedentes', 'Ej. cirugías previas, accidentes, enfermedades pasadas'],
            'enfermedades_cronicas' => ['Enfermedades crónicas', 'Ej. diabetes, insuficiencia renal'],
            'observaciones' => ['Observaciones', 'Notas generales sobre la mascota'],
        ] as $campo => [$etiqueta, $ejemplo])
            <div>
                <label for="{{ $campo }}" class="etiqueta">{{ $etiqueta }}</label>
                <textarea id="{{ $campo }}" name="{{ $campo }}" rows="3" maxlength="5000"
                    placeholder="{{ $ejemplo }}" class="campo">{{ old($campo, $expediente?->$campo) }}</textarea>
                @error($campo)
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('expedientes.show', $mascota) }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">
                {{ $expediente ? 'Guardar cambios' : 'Abrir expediente' }}
            </button>
        </div>
    </form>
@endsection
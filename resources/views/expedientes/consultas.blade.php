@extends('layouts.app')

@section('titulo', 'Historial de consultas de ' . $mascota->nombre)

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

    {{-- Alergias: también visibles en el historial --}}
    @if ($expediente?->alergias)
        <div role="alert" class="mb-6 rounded-xl border border-red-500/40 bg-red-50 px-5 py-4 text-sm text-red-600">
            <p class="font-semibold uppercase tracking-wide">Alergias</p>
            <p class="mt-1 whitespace-pre-line">{{ $expediente->alergias }}</p>
        </div>
    @endif

    @if (! $consultas)
        {{-- Sin expediente, no hay consultas --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
            Esta mascota todavía no tiene expediente clínico ni consultas registradas.
        </div>
    @else
        {{-- Búsqueda por motivo o diagnóstico --}}
        <form method="GET" action="{{ route('expedientes.consultas', $mascota) }}" class="mb-4 flex flex-col gap-3 sm:flex-row">
            <label for="buscar" class="sr-only">Buscar en el historial</label>
            <input id="buscar" type="search" name="buscar" value="{{ $buscar }}" maxlength="100"
                placeholder="Buscar por motivo o diagnóstico" class="campo sm:max-w-md">
            <button type="submit" class="btn-secundario">Buscar</button>
            @if ($buscar !== '')
                <a href="{{ route('expedientes.consultas', $mascota) }}" class="btn-secundario">Limpiar</a>
            @endif
        </form>

        <p class="mb-4 text-sm text-gray-500">
            @if ($consultas->total() === 0)
                @if ($buscar !== '')
                    No se encontraron consultas para "{{ $buscar }}".
                @else
                    Todavía no hay consultas registradas.
                @endif
            @else
                Mostrando {{ $consultas->firstItem() }}–{{ $consultas->lastItem() }} de {{ $consultas->total() }} {{ $consultas->total() === 1 ? 'consulta' : 'consultas' }}, de la más reciente a la más antigua.
            @endif
        </p>

        @foreach ($consultas as $consulta)
            @include('consultas._tarjeta', ['consulta' => $consulta])
        @endforeach

        <div class="mt-4">
            {{ $consultas->links() }}
        </div>
    @endif
@endsection
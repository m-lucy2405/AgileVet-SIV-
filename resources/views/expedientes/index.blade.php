@extends('layouts.app')

@section('titulo', 'Expedientes')

@section('contenido')
    {{-- Búsqueda --}}
    <form method="GET" action="{{ route('expedientes.index') }}" class="mb-4 flex flex-col gap-3 sm:flex-row">
        <label for="buscar" class="sr-only">Buscar</label>
        <input id="buscar" type="search" name="buscar" value="{{ $buscar }}" maxlength="100"
            placeholder="Buscar por mascota, propietario o teléfono" class="campo sm:max-w-md">
        <button type="submit" class="btn-secundario">Buscar</button>
        @if ($buscar !== '')
            <a href="{{ route('expedientes.index') }}" class="btn-secundario">Limpiar</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Mascota</th>
                        <th class="px-4 py-3 font-semibold">Especie</th>
                        <th class="px-4 py-3 font-semibold">Propietario</th>
                        <th class="px-4 py-3 font-semibold">Teléfono</th>
                        <th class="px-4 py-3 font-semibold">Expediente</th>
                        <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($mascotas as $mascota)
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $mascota->nombre }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $mascota->especie }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $mascota->cliente->nombre }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $mascota->cliente->telefono }}</td>
                            <td class="px-4 py-3">
                                @if ($mascota->expediente)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-primario-300">
                                        <span class="h-2 w-2 rounded-full bg-primario-500"></span>
                                        Abierto el {{ $mascota->expediente->fecha_apertura->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-500">Sin expediente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('expedientes.show', $mascota) }}"
                                    class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primario-300 transition hover:bg-primario-500/10">
                                    Ver expediente
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                @if ($buscar !== '')
                                    No se encontraron mascotas para "{{ $buscar }}".
                                @else
                                    No hay mascotas registradas.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 text-xs text-gray-500">
            Mostrando {{ $mascotas->firstItem() ?? 0 }}–{{ $mascotas->lastItem() ?? 0 }}
            de {{ $mascotas->total() }} mascotas
        </div>
    </div>

    <div class="mt-4">
        {{ $mascotas->links() }}
    </div>
@endsection
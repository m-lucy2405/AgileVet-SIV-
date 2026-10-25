@extends('layouts.app')

@section('titulo', 'Auditoría')

@section('contenido')
    <p class="mb-4 text-sm text-gray-600">
        Registro de las acciones realizadas en el sistema: quién hizo cada cambio, cuándo y desde dónde.
    </p>

    {{-- Errores de los filtros --}}
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filtros --}}
    <form method="GET" action="{{ route('auditoria.index') }}"
        class="mb-6 grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="usuario_id" class="etiqueta">Usuario</label>
            <select id="usuario_id" name="usuario_id" class="campo">
                <option value="">Todos</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected(($filtros['usuario_id'] ?? null) == $usuario->id)>
                        {{ $usuario->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="accion" class="etiqueta">Acción</label>
            <select id="accion" name="accion" class="campo">
                <option value="">Todas</option>
                @foreach ($acciones as $clave => $nombre)
                    <option value="{{ $clave }}" @selected(($filtros['accion'] ?? null) === $clave)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="tabla" class="etiqueta">Módulo</label>
            <select id="tabla" name="tabla" class="campo">
                <option value="">Todos</option>
                @foreach ($tablas as $clave => $nombre)
                    <option value="{{ $clave }}" @selected(($filtros['tabla'] ?? null) === $clave)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="desde" class="etiqueta">Desde</label>
            <input id="desde" type="date" name="desde" class="campo" value="{{ $filtros['desde'] ?? '' }}">
        </div>

        <div>
            <label for="hasta" class="etiqueta">Hasta</label>
            <input id="hasta" type="date" name="hasta" class="campo" value="{{ $filtros['hasta'] ?? '' }}">
        </div>

        <div class="flex gap-3 sm:col-span-2 lg:col-span-5 lg:justify-end">
            <a href="{{ route('auditoria.index') }}" class="btn-secundario">Limpiar</a>
            <button type="submit" class="btn-primario">Filtrar</button>
        </div>
    </form>

    {{-- Lista de registros --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Fecha y hora</th>
                        <th class="px-4 py-3 font-semibold">Usuario</th>
                        <th class="px-4 py-3 font-semibold">Acción</th>
                        <th class="px-4 py-3 font-semibold">Módulo</th>
                        <th class="px-4 py-3 font-semibold">Campos</th>
                        <th class="px-4 py-3 text-right font-semibold">Detalle</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($registros as $registro)
                        @php
                            $campos = array_keys($registro->valores_nuevos ?? $registro->valores_anteriores ?? []);
                        @endphp
                        <tr class="transition hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-800">{{ $registro->fecha_accion->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $registro->usuario?->nombre ?? 'Usuario eliminado' }}</td>
                            <td class="px-4 py-3">@include('auditoria._accion', ['auditoria' => $registro])</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $registro->nombreTabla() }}
                                @if ($registro->registro_id)
                                    <span class="text-xs text-gray-500">#{{ $registro->registro_id }}</span>
                                @endif
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 text-xs text-gray-500" title="{{ implode(', ', $campos) }}">
                                {{ $campos ? implode(', ', $campos) : ($registro->descripcion ?? '—') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('auditoria.show', $registro) }}"
                                    class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primario-300 transition hover:bg-primario-500/10">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                No hay registros de auditoría con esos filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 text-xs text-gray-500">
            Mostrando {{ $registros->firstItem() ?? 0 }}–{{ $registros->lastItem() ?? 0 }} de {{ $registros->total() }} registros
        </div>
    </div>

    <div class="mt-4">
        {{ $registros->links() }}
    </div>
@endsection
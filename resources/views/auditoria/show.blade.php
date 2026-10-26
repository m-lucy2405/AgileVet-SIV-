@extends('layouts.app')

@section('titulo', 'Detalle de auditoría')

@section('contenido')
    @php
        // Muestra cada valor de forma legible
        $mostrar = function ($valor): string {
            if ($valor === null || $valor === '') {
                return '(vacío)';
            }
            if (is_bool($valor)) {
                return $valor ? 'Sí' : 'No';
            }
            if (is_array($valor)) {
                return json_encode($valor, JSON_UNESCAPED_UNICODE);
            }

            return (string) $valor;
        };
    @endphp

    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('auditoria.index') }}"
        class="mb-4 inline-block text-sm text-primario-300 hover:underline">
        &larr; Volver a la auditoría
    </a>

    {{-- Datos generales del registro --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-base font-semibold text-gray-800">{{ $auditoria->nombreTabla() }}</h2>
            @include('auditoria._accion', ['auditoria' => $auditoria])
        </div>

        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Fecha y hora</dt>
                <dd class="text-gray-800">{{ $auditoria->fecha_accion->format('d/m/Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Usuario</dt>
                <dd class="text-gray-800">
                    {{ $auditoria->usuario?->nombre ?? 'Usuario eliminado' }}
                    @if ($auditoria->usuario)
                        <span class="block text-xs text-gray-500">{{ $auditoria->usuario->email }}</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Dirección IP</dt>
                <dd class="text-gray-800">{{ $auditoria->ip ?? 'No registrada' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Registro afectado</dt>
                <dd class="text-gray-800">{{ $auditoria->registro_id ? '#' . $auditoria->registro_id : '—' }}</dd>
            </div>
            @if ($auditoria->descripcion)
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Descripción</dt>
                    <dd class="text-gray-800">{{ $auditoria->descripcion }}</dd>
                </div>
            @endif
        </dl>
    </section>

    {{-- Valores antes y después --}}
    @if ($campos !== [])
        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-800">Cambios</h2>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Campo</th>
                            <th class="px-5 py-3 font-semibold">Antes</th>
                            <th class="px-5 py-3 font-semibold">Después</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($campos as $campo)
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-800">{{ ucfirst(str_replace('_', ' ', $campo)) }}</td>
                                <td class="whitespace-pre-line px-5 py-3 text-red-600">
                                    {{ array_key_exists($campo, $anteriores) ? $mostrar($anteriores[$campo]) : '—' }}
                                </td>
                                <td class="whitespace-pre-line px-5 py-3 text-green-800">
                                    {{ array_key_exists($campo, $nuevos) ? $mostrar($nuevos[$campo]) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <p class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-500 shadow-sm">
            Esta acción no registra cambios de datos.
        </p>
    @endif
@endsection
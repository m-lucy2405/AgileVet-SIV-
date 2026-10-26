@extends('layouts.app')

@section('titulo', 'Diagnósticos más frecuentes')

@section('contenido')
    @include('reportes._filtros', ['ruta' => 'reportes.diagnosticos'])

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-800">Los 10 más frecuentes</h2>
            <p class="mt-1 text-xs text-gray-500">
                De {{ $total }} {{ $total === 1 ? 'consulta' : 'consultas' }} en el período. Se agrupan sin distinguir mayúsculas.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[520px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">#</th>
                        <th class="px-5 py-3 font-semibold">Diagnóstico</th>
                        <th class="px-5 py-3 text-right font-semibold">Casos</th>
                        <th class="w-1/3 px-5 py-3 font-semibold">Porcentaje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($diagnosticos as $indice => $fila)
                        <tr>
                            <td class="px-5 py-3 text-gray-500">{{ $indice + 1 }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $fila['diagnostico'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-800">{{ $fila['total'] }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-gray-100">
                                        <div class="h-2 rounded-full bg-acento-500" style="width: {{ $fila['porcentaje'] }}%"></div>
                                    </div>
                                    <span class="w-12 text-right text-xs text-gray-600">{{ $fila['porcentaje'] }} %</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-gray-500">No hay consultas en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
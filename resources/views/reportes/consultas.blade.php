@extends('layouts.app')

@section('titulo', 'Consultas por veterinario')

@section('contenido')
    @include('reportes._filtros', ['ruta' => 'reportes.consultas'])

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:max-w-xs">
        <p class="text-xs uppercase tracking-wide text-gray-500">Total de consultas</p>
        <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $total }}</p>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-800">Por veterinario</h2>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[520px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Veterinario</th>
                        <th class="px-5 py-3 text-right font-semibold">Consultas</th>
                        <th class="w-1/3 px-5 py-3 font-semibold">Porcentaje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($porVeterinario as $fila)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $fila['nombre'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-800">{{ $fila['total'] }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-gray-100">
                                        <div class="h-2 rounded-full bg-primario-500" style="width: {{ $fila['porcentaje'] }}%"></div>
                                    </div>
                                    <span class="w-12 text-right text-xs text-gray-600">{{ $fila['porcentaje'] }} %</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-gray-500">No hay consultas en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
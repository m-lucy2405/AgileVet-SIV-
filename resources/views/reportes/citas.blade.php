@extends('layouts.app')

@section('titulo', 'Reporte de citas')

@section('contenido')
    @include('reportes._filtros', ['ruta' => 'reportes.citas'])

    {{-- Totales --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Total</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $total }}</p>
        </div>
        @foreach ($estados as $clave => $nombre)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ $nombre }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $totales[$clave] }}</p>
            </div>
        @endforeach
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Asistencia</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $asistencia !== null ? $asistencia . ' %' : '—' }}</p>
        </div>
    </div>

    <p class="mb-4 text-xs text-gray-500">
        La asistencia se calcula con las citas que ya debieron ocurrir: atendidas entre atendidas más las que no asistieron.
    </p>

    {{-- Por veterinario --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-800">Por veterinario</h2>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Veterinario</th>
                        @foreach ($estados as $nombre)
                            <th class="px-5 py-3 text-right font-semibold">{{ $nombre }}</th>
                        @endforeach
                        <th class="px-5 py-3 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($porVeterinario as $fila)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $fila['nombre'] }}</td>
                            @foreach ($estados as $clave => $nombre)
                                <td class="px-5 py-3 text-right text-gray-600">{{ $fila['estados'][$clave] }}</td>
                            @endforeach
                            <td class="px-5 py-3 text-right font-semibold text-gray-800">{{ $fila['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($estados) + 2 }}" class="px-5 py-8 text-center text-gray-500">
                                No hay citas en este período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
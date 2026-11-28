@extends('layouts.app')

@section('titulo', 'Reporte de ventas')

@section('contenido')
    @include('reportes._filtros', ['ruta' => 'reportes.ventas'])

    <div class="no-imprimir mb-4 flex justify-end">
        <a href="{{ route('reportes.ventas', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString(), 'formato' => 'csv']) }}"
            class="btn-secundario">Descargar CSV (Excel)</a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Total vendido</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">$ {{ number_format($totalVendido, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Número de ventas</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $cantidad }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Promedio por venta</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">$ {{ number_format($promedio, 2) }}</p>
        </div>
    </div>

    <section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-800">Por método de pago</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Método</th>
                        <th class="px-5 py-3 text-right font-semibold">Ventas</th>
                        <th class="px-5 py-3 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($porMetodo as $fila)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $fila['metodo'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-800">{{ $fila['ventas'] }}</td>
                            <td class="px-5 py-3 text-right text-gray-800">$ {{ number_format($fila['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-gray-500">No hay ventas en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-800">Productos más vendidos</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Código</th>
                        <th class="px-5 py-3 font-semibold">Producto</th>
                        <th class="px-5 py-3 text-right font-semibold">Unidades</th>
                        <th class="px-5 py-3 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($masVendidos as $fila)
                        <tr>
                            <td class="px-5 py-3 text-gray-600">{{ $fila->codigo_producto }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $fila->nombre_producto }}</td>
                            <td class="px-5 py-3 text-right text-gray-800">{{ (int) $fila->unidades }}</td>
                            <td class="px-5 py-3 text-right text-gray-800">$ {{ number_format((float) $fila->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-gray-500">No hay productos vendidos en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@extends('layouts.app')

@section('titulo', 'Reporte de inventario')

@section('contenido')
    @include('reportes._filtros', ['ruta' => 'reportes.inventario'])

    <div class="no-imprimir mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600">Existencias al <strong>{{ $hoy->format('d/m/Y') }}</strong></p>
        <a href="{{ route('reportes.inventario', ['formato' => 'csv']) }}" class="btn-secundario">Descargar CSV (Excel)</a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Productos activos</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $totalProductos }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Valor al costo</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">$ {{ number_format($valorInventario, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Con stock bajo</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $conStockBajo }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Lotes por vencer ({{ $diasPorVencer }} días)</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $lotesPorVencer->count() }}</p>
        </div>
    </div>

    <section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-800">Existencias</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Código</th>
                        <th class="px-5 py-3 font-semibold">Producto</th>
                        <th class="px-5 py-3 font-semibold">Categoría</th>
                        <th class="px-5 py-3 text-right font-semibold">Existencia</th>
                        <th class="px-5 py-3 text-right font-semibold">Mínimo</th>
                        <th class="px-5 py-3 font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($productos as $producto)
                        <tr>
                            <td class="px-5 py-3 text-gray-600">{{ $producto->codigo }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $producto->nombre }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</td>
                            <td class="px-5 py-3 text-right text-gray-800">{{ $producto->stock }} {{ $producto->unidad }}</td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ $producto->stock_minimo }}</td>
                            <td class="px-5 py-3">
                                @if ($producto->tieneStockBajo())
                                    <span class="whitespace-nowrap rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-600">Stock bajo</span>
                                @else
                                    <span class="whitespace-nowrap rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Normal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">No hay productos activos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-800">Lotes vencidos o por vencer</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Producto</th>
                        <th class="px-5 py-3 font-semibold">Lote</th>
                        <th class="px-5 py-3 font-semibold">Caduca</th>
                        <th class="px-5 py-3 text-right font-semibold">Disponible</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($lotesPorVencer as $lote)
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $lote->producto?->nombre }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $lote->codigo_lote }}</td>
                            <td class="px-5 py-3 {{ $lote->fecha_caducidad->lt($hoy) ? 'font-semibold text-red-600' : 'text-gray-800' }}">
                                {{ $lote->fecha_caducidad->format('d/m/Y') }}{{ $lote->fecha_caducidad->lt($hoy) ? ' (vencido)' : '' }}
                            </td>
                            <td class="px-5 py-3 text-right text-gray-800">{{ $lote->cantidad_disponible }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-gray-500">No hay lotes por vencer en los próximos {{ $diasPorVencer }} días.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

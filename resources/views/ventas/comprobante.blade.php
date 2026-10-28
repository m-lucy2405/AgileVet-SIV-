@extends('layouts.app')

@section('titulo', 'Comprobante ' . $venta->folio)

@section('contenido')
    <style>
        @media print {
            #sidebar, #overlay, header:not(.ticket-header), footer, .no-imprimir {
                display: none !important;
            }

            .lg\:pl-64 {
                padding-left: 0 !important;
            }

            body {
                background: #fff !important;
            }
        }
    </style>

    <div class="no-imprimir mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('ventas.index') }}" class="text-sm text-primario-300 hover:underline">&larr; Volver a ventas</a>
        <button type="button" onclick="window.print()" class="btn-secundario">Imprimir comprobante</button>
    </div>

    @if (session('status'))
        <div role="status" class="no-imprimir mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <article class="mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <header class="ticket-header flex flex-wrap items-start justify-between gap-4 border-b border-dashed border-gray-300 pb-5">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primario-700">Agro-Veterinaria AgileVet</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">Ticket de venta</h1>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-wide text-gray-500">Folio</p>
                <p class="font-mono text-lg font-semibold text-gray-900">{{ $venta->folio }}</p>
            </div>
        </header>

        <dl class="grid gap-3 border-b border-dashed border-gray-300 py-5 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Fecha y hora</dt>
                <dd class="mt-1 text-gray-800">{{ $venta->fecha_venta->format('d/m/Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Vendedor</dt>
                <dd class="mt-1 text-gray-800">{{ $venta->usuario->nombre }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Medio de pago</dt>
                <dd class="mt-1 text-gray-800">{{ $metodosPago[$venta->metodo_pago] }}</dd>
            </div>
        </dl>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[540px] text-left text-sm">
                <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="py-3 pr-3 font-semibold">Artículo</th>
                        <th class="px-3 py-3 font-semibold">Lote</th>
                        <th class="px-3 py-3 text-right font-semibold">Cant.</th>
                        <th class="px-3 py-3 text-right font-semibold">Precio</th>
                        <th class="py-3 pl-3 text-right font-semibold">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($venta->detalles as $detalle)
                        <tr>
                            <td class="py-3 pr-3">
                                <span class="block font-medium text-gray-800">{{ $detalle->nombre_producto }}</span>
                                <span class="text-xs text-gray-500">{{ $detalle->codigo_producto }}</span>
                            </td>
                            <td class="px-3 py-3 text-gray-600">{{ $detalle->codigo_lote }}</td>
                            <td class="px-3 py-3 text-right text-gray-800">{{ $detalle->cantidad }}</td>
                            <td class="whitespace-nowrap px-3 py-3 text-right text-gray-800">$ {{ number_format((float) $detalle->precio_unitario, 2) }}</td>
                            <td class="whitespace-nowrap py-3 pl-3 text-right font-medium text-gray-800">$ {{ number_format((float) $detalle->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300">
                        <th colspan="4" class="py-4 pr-3 text-right text-base font-semibold text-gray-900">Total</th>
                        <td class="whitespace-nowrap py-4 pl-3 text-right text-lg font-bold text-gray-900">
                            $ {{ number_format((float) $venta->total, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="mt-5 border-t border-dashed border-gray-300 pt-4 text-center text-xs text-gray-500">
            Venta confirmada. Este comprobante refleja los precios y artículos registrados al momento de la compra.
        </p>
    </article>
@endsection

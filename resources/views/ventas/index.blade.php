@extends('layouts.app')

@section('titulo', 'Ventas')

@section('contenido')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600">Ventas confirmadas y comprobantes del punto de venta.</p>
        <a href="{{ route('ventas.create') }}" class="btn-primario">+ Nueva venta</a>
    </div>

    @if (session('status'))
        <div role="status" class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Folio</th>
                        <th class="px-5 py-3 font-semibold">Fecha</th>
                        <th class="px-5 py-3 font-semibold">Vendedor</th>
                        <th class="px-5 py-3 font-semibold">Pago</th>
                        <th class="px-5 py-3 text-right font-semibold">Total</th>
                        <th class="px-5 py-3 text-right font-semibold">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ventas as $venta)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-gray-800">{{ $venta->folio }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $venta->fecha_venta->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $venta->usuario->nombre }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ \App\Models\Venta::METODOS_PAGO[$venta->metodo_pago] }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-800">
                                $ {{ number_format((float) $venta->total, 2) }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('ventas.comprobante', $venta) }}" class="text-primario-300 hover:underline">
                                    Ver ticket
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                                Todavía no hay ventas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-3">
            {{ $ventas->links() }}
        </div>
    </section>
@endsection

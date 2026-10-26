@extends('layouts.app')

@section('titulo', 'Kárdex de ' . $producto->nombre)

@section('contenido')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('productos.index') }}" class="text-sm text-primario-300 hover:underline">&larr; Volver al inventario</a>

        @if ($esAdministrador)
            <div class="flex gap-3">
                <a href="{{ route('productos.edit', $producto) }}" class="btn-secundario">Editar producto</a>
                @if ($producto->activo)
                    <a href="{{ route('movimientos.create', $producto) }}" class="btn-primario">+ Registrar movimiento</a>
                @endif
            </div>
        @endif
    </div>

    @if (session('status'))
        <div role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Alertas del producto --}}
    @if (! $producto->activo)
        <div class="mb-4 rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-600">
            Este producto está <strong>inactivo</strong>: no aparece en el catálogo y no admite movimientos.
        </div>
    @endif
    @if ($producto->estaVencido())
        <div role="alert" class="mb-4 rounded-lg border border-red-500/40 bg-red-50 px-4 py-3 text-sm text-red-600">
            <strong>Producto vencido</strong> el {{ $producto->fecha_vencimiento->format('d/m/Y') }}. No debe venderse ni aplicarse.
        </div>
    @endif
    @if ($producto->tieneStockBajo())
        <div role="alert" class="mb-4 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-300">
            <strong>Stock bajo:</strong> quedan {{ $producto->stock }} {{ $producto->unidad }} y el mínimo es {{ $producto->stock_minimo }}. Conviene reponer.
        </div>
    @endif

    {{-- Datos del producto --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2 class="text-base font-semibold text-gray-800">{{ $producto->nombre }}</h2>
            <span class="text-sm text-gray-500">{{ $producto->codigo }} · {{ $producto->categoria->nombre }}</span>
        </div>
        @if ($producto->descripcion)
            <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $producto->descripcion }}</p>
        @endif

        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3 lg:grid-cols-5">
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Stock actual</dt>
                <dd class="text-lg font-semibold {{ $producto->tieneStockBajo() ? 'text-amber-300' : 'text-gray-800' }}">
                    {{ $producto->stock }} {{ $producto->unidad }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Stock mínimo</dt>
                <dd class="text-gray-800">{{ $producto->stock_minimo }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Precio de compra</dt>
                <dd class="text-gray-800">$ {{ number_format($producto->precio_compra, 2) }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Precio de venta</dt>
                <dd class="text-gray-800">$ {{ number_format($producto->precio_venta, 2) }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-500">Vencimiento</dt>
                <dd class="{{ $producto->estaVencido() ? 'font-semibold text-red-600' : 'text-gray-800' }}">
                    {{ $producto->fecha_vencimiento?->format('d/m/Y') ?? 'Sin fecha' }}
                </dd>
            </div>
        </dl>
    </section>

    {{-- Filtros del kárdex --}}
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('productos.show', $producto) }}"
        class="mb-6 grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-4">
        <div>
            <label for="tipo" class="etiqueta">Tipo</label>
            <select id="tipo" name="tipo" class="campo">
                <option value="">Todos</option>
                @foreach ($tipos as $clave => $nombre)
                    <option value="{{ $clave }}" @selected(($filtros['tipo'] ?? null) === $clave)>{{ $nombre }}</option>
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
        <div class="flex items-end gap-3">
            <button type="submit" class="btn-primario">Filtrar</button>
            <a href="{{ route('productos.show', $producto) }}" class="btn-secundario">Limpiar</a>
        </div>
    </form>

    {{-- Totales de lo filtrado --}}
    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Movimientos</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $totales['cantidad'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Unidades que entraron</p>
            <p class="mt-1 text-2xl font-semibold text-green-800">+{{ $totales['entradas'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Unidades que salieron</p>
            <p class="mt-1 text-2xl font-semibold text-red-600">−{{ $totales['salidas'] }}</p>
        </div>
    </div>

    {{-- Movimientos --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-800">Kárdex</h2>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Fecha y hora</th>
                        <th class="px-5 py-3 font-semibold">Tipo</th>
                        <th class="px-5 py-3 font-semibold">Motivo</th>
                        <th class="px-5 py-3 text-right font-semibold">Cantidad</th>
                        <th class="px-5 py-3 text-right font-semibold">Stock</th>
                        <th class="px-5 py-3 font-semibold">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($movimientos as $movimiento)
                        @php $sube = $movimiento->stock_nuevo > $movimiento->stock_anterior; @endphp
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-800">{{ $movimiento->fecha_movimiento->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $movimiento->tipo === 'entrada' ? 'bg-green-50 text-green-800' : ($movimiento->tipo === 'salida' ? 'bg-red-50 text-red-600' : 'bg-amber-500/15 text-amber-300') }}">
                                    {{ $movimiento->nombreTipo() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $movimiento->motivo }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold {{ $sube ? 'text-green-800' : 'text-red-600' }}">
                                {{ $sube ? '+' : '−' }}{{ $movimiento->cantidad }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right text-gray-600">
                                {{ $movimiento->stock_anterior }} → <strong class="text-gray-800">{{ $movimiento->stock_nuevo }}</strong>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $movimiento->usuario?->nombre ?? 'Usuario eliminado' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">No hay movimientos con esos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">
        {{ $movimientos->links() }}
    </div>
@endsection
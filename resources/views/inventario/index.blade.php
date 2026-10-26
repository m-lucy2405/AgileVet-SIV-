@extends('layouts.app')

@section('titulo', 'Inventario')

@section('contenido')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600">Catálogo de productos y existencias.</p>
        @if ($esAdministrador)
            <a href="{{ route('productos.create') }}" class="btn-primario">+ Nuevo producto</a>
        @endif
    </div>

    @if (session('status'))
        <div role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Resumen: atajos a los filtros de alerta --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('productos.index') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primario-500">
            <p class="text-xs uppercase tracking-wide text-gray-500">Productos activos</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $resumen['activos'] }}</p>
        </a>
        <a href="{{ route('productos.index', ['alerta' => 'stock_bajo']) }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primario-500">
            <p class="text-xs uppercase tracking-wide text-gray-500">Con stock bajo</p>
            <p class="mt-1 text-2xl font-semibold {{ $resumen['stock_bajo'] > 0 ? 'text-amber-300' : 'text-gray-800' }}">{{ $resumen['stock_bajo'] }}</p>
        </a>
        <a href="{{ route('productos.index', ['alerta' => 'por_vencer']) }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primario-500">
            <p class="text-xs uppercase tracking-wide text-gray-500">Vencen en {{ \App\Http\Controllers\ProductoController::DIAS_POR_VENCER }} días o ya vencidos</p>
            <p class="mt-1 text-2xl font-semibold {{ $resumen['por_vencer'] > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $resumen['por_vencer'] }}</p>
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('productos.index') }}"
        class="mb-6 grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="buscar" class="etiqueta">Buscar</label>
            <input id="buscar" type="search" name="buscar" maxlength="100" placeholder="Nombre o código"
                class="campo" value="{{ $filtros['buscar'] }}">
        </div>
        <div>
            <label for="categoria_id" class="etiqueta">Categoría</label>
            <select id="categoria_id" name="categoria_id" class="campo">
                <option value="">Todas</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(($filtros['categoria_id'] ?? null) == $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="alerta" class="etiqueta">Alerta</label>
            <select id="alerta" name="alerta" class="campo">
                <option value="">Sin filtro</option>
                <option value="stock_bajo" @selected(($filtros['alerta'] ?? null) === 'stock_bajo')>Stock bajo</option>
                <option value="por_vencer" @selected(($filtros['alerta'] ?? null) === 'por_vencer')>Por vencer o vencidos</option>
            </select>
        </div>
        <div>
            <label for="estado" class="etiqueta">Estado</label>
            <select id="estado" name="estado" class="campo">
                <option value="activos" @selected($filtros['estado'] === 'activos')>Activos</option>
                <option value="inactivos" @selected($filtros['estado'] === 'inactivos')>Inactivos</option>
                <option value="todos" @selected($filtros['estado'] === 'todos')>Todos</option>
            </select>
        </div>
        <div class="flex gap-3 sm:col-span-2 lg:col-span-4 lg:justify-end">
            <a href="{{ route('productos.index') }}" class="btn-secundario">Limpiar</a>
            <button type="submit" class="btn-primario">Filtrar</button>
        </div>
    </form>

    {{-- Lista --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Código</th>
                        <th class="px-4 py-3 font-semibold">Producto</th>
                        <th class="px-4 py-3 font-semibold">Categoría</th>
                        <th class="px-4 py-3 text-right font-semibold">Stock</th>
                        <th class="px-4 py-3 text-right font-semibold">Precio venta</th>
                        <th class="px-4 py-3 font-semibold">Vencimiento</th>
                        @if ($esAdministrador)
                            <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($productos as $producto)
                        @php
                            $porVencer = $producto->fecha_vencimiento
                                && ! $producto->estaVencido()
                                && $producto->fecha_vencimiento->lte(today()->addDays(\App\Http\Controllers\ProductoController::DIAS_POR_VENCER));
                        @endphp
                        <tr class="transition hover:bg-gray-50 {{ $producto->activo ? '' : 'opacity-60' }}">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $producto->codigo }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800">{{ $producto->nombre }}</span>
                                @unless ($producto->activo)
                                    <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Inactivo</span>
                                @endunless
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $producto->categoria->nombre }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <span class="{{ $producto->tieneStockBajo() ? 'font-semibold text-amber-300' : 'text-gray-800' }}">
                                    {{ $producto->stock }} {{ $producto->unidad }}
                                </span>
                                @if ($producto->tieneStockBajo())
                                    <span class="block text-xs text-amber-300">Stock bajo (mín. {{ $producto->stock_minimo }})</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-gray-800">$ {{ number_format($producto->precio_venta, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if (! $producto->fecha_vencimiento)
                                    <span class="text-gray-500">—</span>
                                @elseif ($producto->estaVencido())
                                    <span class="font-semibold text-red-600">{{ $producto->fecha_vencimiento->format('d/m/Y') }} (vencido)</span>
                                @elseif ($porVencer)
                                    <span class="font-semibold text-amber-300">{{ $producto->fecha_vencimiento->format('d/m/Y') }} (por vencer)</span>
                                @else
                                    <span class="text-gray-600">{{ $producto->fecha_vencimiento->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            @if ($esAdministrador)
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    @if ($producto->activo)
                                        <a href="{{ route('movimientos.create', $producto) }}"
                                            class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primario-300 transition hover:bg-primario-500/10">
                                            Movimiento
                                        </a>
                                    @endif
                                    <a href="{{ route('productos.edit', $producto) }}"
                                        class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primario-300 transition hover:bg-primario-500/10">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('productos.estado', $producto) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="rounded-md px-2.5 py-1.5 text-xs font-medium transition {{ $producto->activo ? 'text-red-600 hover:bg-red-50' : 'text-primario-300 hover:bg-primario-500/10' }}">
                                            {{ $producto->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $esAdministrador ? 7 : 6 }}" class="px-4 py-10 text-center text-gray-500">
                                No hay productos con esos filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 text-xs text-gray-500">
            Mostrando {{ $productos->firstItem() ?? 0 }}–{{ $productos->lastItem() ?? 0 }} de {{ $productos->total() }} productos
        </div>
    </div>

    <div class="mt-4">
        {{ $productos->links() }}
    </div>
@endsection
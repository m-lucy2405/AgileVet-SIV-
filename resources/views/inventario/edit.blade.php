@extends('layouts.app')

@section('titulo', 'Editar producto')

@section('contenido')
    <a href="{{ route('productos.index') }}" class="mb-4 btn-volver">
        &larr; Volver al inventario
    </a>

    @if (session('status'))
        <div role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudieron guardar los cambios:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- El stock no se edita aquí: cambia con entradas, salidas y ajustes --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-gray-500">Stock actual</p>
        <p class="mt-1 text-2xl font-semibold text-gray-800">{{ $producto->stock }} {{ $producto->unidad }}</p>
        <p class="mt-1 text-xs text-gray-500">
            El stock no se modifica desde este formulario: se cambia registrando entradas, salidas o ajustes.
        </p>
    </section>

    {{-- Lotes con existencias: aquí se corrige la fecha de caducidad --}}
    <section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-800">Lotes con existencias</h2>
            <p class="mt-1 text-xs text-gray-500">
                La fecha de caducidad de cada lote es la que usan las alertas de "por vencer" y las ventas.
                Corrígela aquí si se registró mal.
            </p>
        </div>

        @if ($producto->lotes->isEmpty())
            <p class="px-5 py-6 text-sm text-gray-500">
                Este producto no tiene lotes con existencias. Registra una entrada para agregar uno.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Lote</th>
                            <th class="px-5 py-3 text-right font-semibold">Disponible</th>
                            <th class="px-5 py-3 font-semibold">Caducidad</th>
                            <th class="px-5 py-3 font-semibold">Corregir fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($producto->lotes as $lote)
                            @php
                                $vencido = $lote->fecha_caducidad?->lt(today()) ?? false;
                                $porVencer = $lote->fecha_caducidad && ! $vencido
                                    && $lote->fecha_caducidad->lte(today()->addDays(\App\Http\Controllers\ProductoController::DIAS_POR_VENCER));
                            @endphp
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-800">{{ $lote->codigo_lote }}</td>
                                <td class="px-5 py-3 text-right text-gray-800">{{ $lote->cantidad_disponible }} {{ $producto->unidad }}</td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    @if (! $lote->fecha_caducidad)
                                        <span class="text-gray-500">Sin fecha</span>
                                    @elseif ($vencido)
                                        <span class="font-semibold text-red-600">{{ $lote->fecha_caducidad->format('d/m/Y') }} (vencido)</span>
                                    @elseif ($porVencer)
                                        <span class="font-semibold text-amber-300">{{ $lote->fecha_caducidad->format('d/m/Y') }} (por vencer)</span>
                                    @else
                                        <span class="text-gray-600">{{ $lote->fecha_caducidad->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <form method="POST" action="{{ route('lotes.caducidad', $lote) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label for="fecha-lote-{{ $lote->id }}" class="sr-only">Nueva fecha del lote {{ $lote->codigo_lote }}</label>
                                        <input id="fecha-lote-{{ $lote->id }}" name="fecha_caducidad" type="date" class="campo max-w-[11rem]"
                                            value="{{ $lote->fecha_caducidad?->toDateString() }}">
                                        <button type="submit" class="btn-secundario whitespace-nowrap">Corregir fecha</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <p class="mb-4 text-sm text-gray-600">Los campos con <span class="text-red-500">*</span> son obligatorios.</p>

    <form method="POST" action="{{ route('productos.update', $producto) }}"
        class="space-y-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PUT')

        @include('inventario._formulario', ['producto' => $producto])

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('productos.index') }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Guardar cambios</button>
        </div>
    </form>
@endsection
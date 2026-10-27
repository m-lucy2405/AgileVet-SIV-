@extends('layouts.app')

@section('titulo', 'Registrar movimiento')

@section('contenido')
    <a href="{{ route('productos.index') }}" class="mb-4 inline-block text-sm text-primario-300 hover:underline">
        &larr; Volver al inventario
    </a>

    {{-- Producto y stock actual --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-gray-800">{{ $producto->nombre }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $producto->codigo }} · {{ $producto->categoria->nombre }}</p>
        <p class="mt-3 text-sm text-gray-600">
            Stock actual: <strong class="text-gray-800">{{ $producto->stock }} {{ $producto->unidad }}</strong>
            @if ($producto->tieneStockBajo())
                <span class="ml-2 text-amber-300">(stock bajo, mínimo {{ $producto->stock_minimo }})</span>
            @endif
        </p>
    </section>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudo registrar el movimiento:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mb-4 text-sm text-gray-600">Los campos con <span class="text-red-500">*</span> son obligatorios.</p>

    <form method="POST" action="{{ route('movimientos.store', $producto) }}" id="form-movimiento"
        data-stock="{{ $producto->stock }}" data-unidad="{{ $producto->unidad }}"
        class="space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf

        {{-- Tipo de movimiento --}}
        <fieldset>
            <legend class="etiqueta">Tipo de movimiento <span class="text-red-500">*</span></legend>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($tipos as $clave => $nombre)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition has-[:checked]:border-primario-500">
                        <input type="radio" name="tipo" value="{{ $clave }}" class="mt-1" required
                            @checked(old('tipo', 'entrada') === $clave)>
                        <span>
                            <span class="block text-sm font-medium text-gray-800">{{ $nombre }}</span>
                            <span class="block text-xs text-gray-500">
                                @switch($clave)
                                    @case('entrada') Suma unidades al stock. @break
                                    @case('salida') Resta unidades del stock. @break
                                    @case('ajuste') Pone el stock que contaste. @break
                                @endswitch
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('tipo')
                <p class="error-campo">{{ $message }}</p>
            @enderror
        </fieldset>

        {{-- Cantidad --}}
        <div class="sm:max-w-xs">
            <label for="cantidad" class="etiqueta">
                <span id="etiqueta-cantidad">Cantidad</span> <span class="text-red-500">*</span>
            </label>
            <input id="cantidad" name="cantidad" type="number" step="1" min="0" required class="campo"
                value="{{ old('cantidad') }}">
            <p id="vista-previa" class="mt-1 text-xs text-gray-500"></p>
            @error('cantidad')
                <p class="error-campo">{{ $message }}</p>
            @enderror
        </div>

        {{-- Motivo --}}
        <div>
            <label for="motivo" class="etiqueta">Motivo <span class="text-red-500">*</span></label>
            <input id="motivo" name="motivo" type="text" required maxlength="255" list="motivos-sugeridos"
                placeholder="Elige una sugerencia o escribe otro motivo" class="campo" value="{{ old('motivo') }}">
            <datalist id="motivos-sugeridos"></datalist>
            @error('motivo')
                <p class="error-campo">{{ $message }}</p>
            @enderror
        </div>

        <fieldset class="grid gap-4 border-t border-gray-100 pt-4 sm:grid-cols-3">
            <legend class="etiqueta">Datos del lote para entradas (opcionales)</legend>
            <div>
                <label for="codigo_lote" class="etiqueta">Código del lote</label>
                <input id="codigo_lote" name="codigo_lote" type="text" maxlength="100" class="campo"
                    value="{{ old('codigo_lote') }}">
                @error('codigo_lote')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="fecha_caducidad" class="etiqueta">Fecha de caducidad</label>
                <input id="fecha_caducidad" name="fecha_caducidad" type="date" class="campo"
                    value="{{ old('fecha_caducidad') }}">
                @error('fecha_caducidad')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="costo_compra" class="etiqueta">Costo unitario ($)</label>
                <input id="costo_compra" name="costo_compra" type="number" min="0" step="0.01" class="campo"
                    value="{{ old('costo_compra', $producto->precio_compra) }}">
                @error('costo_compra')
                    <p class="error-campo">{{ $message }}</p>
                @enderror
            </div>
            <p class="text-xs text-gray-500 sm:col-span-3">
                Si no indicas código, se generará uno automáticamente. Las salidas administrativas retiran stock de lotes
                en orden de caducidad; solo las ventas bloquean lotes vencidos.
            </p>
        </fieldset>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
            <a href="{{ route('productos.index') }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Registrar movimiento</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Cambia la etiqueta, las sugerencias y la vista previa según el tipo de movimiento
        (() => {
            const formulario = document.getElementById('form-movimiento');
            const stock = Number(formulario.dataset.stock);
            const unidad = formulario.dataset.unidad;
            const cantidad = document.getElementById('cantidad');
            const etiqueta = document.getElementById('etiqueta-cantidad');
            const vistaPrevia = document.getElementById('vista-previa');
            const sugerencias = document.getElementById('motivos-sugeridos');
            const motivos = @json($motivos);

            const tipoElegido = () => formulario.querySelector('input[name="tipo"]:checked')?.value ?? 'entrada';

            const actualizar = () => {
                const tipo = tipoElegido();
                const valor = cantidad.value === '' ? null : Number(cantidad.value);

                etiqueta.textContent = tipo === 'ajuste' ? 'Stock contado' : 'Cantidad';
                cantidad.min = tipo === 'ajuste' ? 0 : 1;

                sugerencias.innerHTML = '';
                (motivos[tipo] ?? []).forEach((motivo) => {
                    const opcion = document.createElement('option');
                    opcion.value = motivo;
                    sugerencias.append(opcion);
                });

                if (valor === null) {
                    vistaPrevia.textContent = '';
                    vistaPrevia.className = 'mt-1 text-xs text-gray-500';
                    return;
                }

                const nuevo = tipo === 'entrada' ? stock + valor : tipo === 'salida' ? stock - valor : valor;
                vistaPrevia.textContent = nuevo < 0
                    ? `No alcanza: solo hay ${stock} ${unidad}.`
                    : `El stock quedará en ${nuevo} ${unidad}.`;
                vistaPrevia.className = nuevo < 0 ? 'mt-1 text-xs font-semibold text-red-600' : 'mt-1 text-xs text-gray-500';
            };

            formulario.addEventListener('change', actualizar);
            cantidad.addEventListener('input', actualizar);
            actualizar();
        })();
    </script>
@endpush
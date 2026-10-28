@extends('layouts.app')

@section('titulo', 'Nueva venta')

@section('contenido')
    <div class="mb-5 flex items-center justify-between gap-3">
        <a href="{{ route('ventas.index') }}" class="text-sm text-primario-300 hover:underline">&larr; Volver a ventas</a>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudo completar la venta:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($productos->isEmpty())
        <p class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-5 text-sm text-amber-800">
            No hay productos activos con stock vigente disponible para vender.
        </p>
    @else
        <form method="POST" action="{{ route('ventas.store') }}" class="space-y-5">
            @csrf

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">Artículos</h2>
                        <p class="mt-1 text-xs text-gray-500">El sistema descontará primero los lotes con caducidad más próxima.</p>
                    </div>
                    <button type="button" class="btn-secundario" data-agregar-articulo>+ Agregar artículo</button>
                </div>

                <div id="articulos-venta" class="space-y-3">
                    <div class="grid gap-3 rounded-lg border border-gray-100 p-3 sm:grid-cols-[1fr_9rem_auto] sm:items-end" data-fila-articulo>
                        <div>
                            <label for="producto-0" class="etiqueta">Producto</label>
                            <select id="producto-0" name="items[0][producto_id]" class="campo" required data-producto>
                                <option value="">Selecciona un producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}"
                                        data-precio="{{ $producto->precio_venta }}"
                                        data-stock="{{ $producto->stock_vigente }}">
                                        {{ $producto->codigo }} · {{ $producto->nombre }}
                                        ({{ $producto->stock_vigente }} {{ $producto->unidad }} disponibles)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="cantidad-0" class="etiqueta">Cantidad</label>
                            <input id="cantidad-0" name="items[0][cantidad]" class="campo" type="number" min="1" max="100000" value="1" required data-cantidad>
                        </div>
                        <button type="button" class="btn-secundario sm:mb-0.5" data-quitar-articulo aria-label="Quitar artículo" disabled>
                            Quitar
                        </button>
                        <p class="text-right text-sm font-medium text-gray-700 sm:col-span-3" data-subtotal aria-live="polite"></p>
                    </div>
                </div>

                <template id="plantilla-articulo">
                    <div class="grid gap-3 rounded-lg border border-gray-100 p-3 sm:grid-cols-[1fr_9rem_auto] sm:items-end" data-fila-articulo>
                        <div>
                            <label for="producto-__INDEX__" class="etiqueta">Producto</label>
                            <select id="producto-__INDEX__" name="items[__INDEX__][producto_id]" class="campo" required data-producto>
                                <option value="">Selecciona un producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}"
                                        data-precio="{{ $producto->precio_venta }}"
                                        data-stock="{{ $producto->stock_vigente }}">
                                        {{ $producto->codigo }} · {{ $producto->nombre }}
                                        ({{ $producto->stock_vigente }} {{ $producto->unidad }} disponibles)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="cantidad-__INDEX__" class="etiqueta">Cantidad</label>
                            <input id="cantidad-__INDEX__" name="items[__INDEX__][cantidad]" class="campo" type="number" min="1" max="100000" value="1" required data-cantidad>
                        </div>
                        <button type="button" class="btn-secundario sm:mb-0.5" data-quitar-articulo>Quitar</button>
                        <p class="text-right text-sm font-medium text-gray-700 sm:col-span-3" data-subtotal aria-live="polite"></p>
                    </div>
                </template>
            </section>

            <section class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                <div class="sm:w-72">
                    <label for="metodo_pago" class="etiqueta">Medio de pago</label>
                    <select id="metodo_pago" name="metodo_pago" class="campo" required>
                        @foreach ($metodosPago as $clave => $nombre)
                            <option value="{{ $clave }}" @selected(old('metodo_pago', 'efectivo') === $clave)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-right text-lg font-semibold text-gray-800" aria-live="polite">
                    Total estimado: <span id="total-venta">$ 0.00</span>
                </p>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('ventas.index') }}" class="btn-secundario">Cancelar</a>
                <button type="submit" class="btn-primario">Confirmar venta</button>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const lista = document.getElementById('articulos-venta');
            const plantilla = document.getElementById('plantilla-articulo');
            const total = document.getElementById('total-venta');
            let siguienteIndice = 1;
            const formatoMoneda = new Intl.NumberFormat('es-SV', {
                style: 'currency',
                currency: 'USD',
            });

            const actualizarTotal = () => {
                let centavos = 0;

                lista.querySelectorAll('[data-fila-articulo]').forEach((fila) => {
                    const producto = fila.querySelector('[data-producto]');
                    const cantidad = Number(fila.querySelector('[data-cantidad]').value) || 0;
                    const precio = Number(producto.selectedOptions[0]?.dataset.precio) || 0;
                    const subtotalCentavos = Math.round(precio * cantidad * 100);
                    centavos += subtotalCentavos;
                    fila.querySelector('[data-subtotal]').textContent = producto.value
                        ? `Subtotal estimado: ${formatoMoneda.format(subtotalCentavos / 100)}`
                        : '';
                    fila.querySelector('[data-quitar-articulo]').disabled = lista.children.length === 1;
                });

                total.textContent = formatoMoneda.format(centavos / 100);
            };

            document.querySelector('[data-agregar-articulo]').addEventListener('click', () => {
                const fila = plantilla.content.firstElementChild.cloneNode(true);
                fila.innerHTML = fila.innerHTML.replaceAll('__INDEX__', String(siguienteIndice));
                siguienteIndice += 1;
                lista.append(fila);
                actualizarTotal();
            });

            lista.addEventListener('input', actualizarTotal);
            lista.addEventListener('change', actualizarTotal);
            lista.addEventListener('click', (evento) => {
                if (evento.target.matches('[data-quitar-articulo]') && lista.children.length > 1) {
                    evento.target.closest('[data-fila-articulo]').remove();
                    actualizarTotal();
                }
            });

            actualizarTotal();
        })();
    </script>
@endpush

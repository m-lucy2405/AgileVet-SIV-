@extends('layouts.app')

@section('titulo', 'Nueva consulta')

@section('contenido')
    @php
        // Filas de medicamentos: las que se enviaron (si hubo error) o una vacía para empezar
        $filas = old('tratamientos', [[]]);
        if (empty($filas)) {
            $filas = [[]];
        }
    @endphp

    <a href="{{ route('expedientes.show', $mascota) }}" class="mb-4 btn-volver">
        &larr; Volver al expediente
    </a>

    {{-- Mascota y cita de origen --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-gray-800">{{ $mascota->nombre }}</h2>
        <p class="mt-1 text-sm text-gray-500">
            {{ $mascota->especie }} · Propietario: {{ $mascota->cliente->nombre }} · {{ $mascota->cliente->telefono }}
        </p>
        @if ($cita)
            <p class="mt-3 text-sm text-gray-700">
                Consulta de la cita del <strong>{{ $cita->fecha_cita->format('d/m/Y H:i') }}</strong> · {{ $cita->motivo }}.
                Al guardar, la cita quedará como <strong>atendida</strong>.
            </p>
        @endif
    </section>

    {{-- Alergias: visibles antes de recetar --}}
    @if ($expediente?->alergias)
        <div role="alert" class="mb-6 rounded-xl border border-red-500/40 bg-red-50 px-5 py-4 text-sm text-red-600">
            <p class="font-semibold uppercase tracking-wide">Alergias</p>
            <p class="mt-1 whitespace-pre-line">{{ $expediente->alergias }}</p>
        </div>
    @endif

    {{-- Errores del servidor --}}
    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudo guardar la consulta:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="mb-4 text-sm text-gray-600">Los campos con <span class="text-red-500">*</span> son obligatorios.</p>

    <form method="POST" action="{{ route('consultas.store', $mascota) }}" class="space-y-6">
        @csrf
        <input type="hidden" name="cita_id" value="{{ old('cita_id', $cita?->id) }}">

        {{-- Abrir expediente (solo si todavía no existe) --}}
        @unless ($expediente)
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-gray-800">Abrir expediente</h2>
                <p class="mt-1 text-xs text-gray-500">
                    Es la primera consulta de esta mascota. Estos datos quedarán en su expediente.
                </p>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ([
                        'antecedentes' => 'Antecedentes',
                        'alergias' => 'Alergias',
                        'enfermedades_cronicas' => 'Enfermedades crónicas',
                    ] as $campo => $etiqueta)
                        <div>
                            <label for="{{ $campo }}" class="etiqueta">{{ $etiqueta }}</label>
                            <textarea id="{{ $campo }}" name="{{ $campo }}" rows="3" maxlength="5000"
                                class="campo">{{ old($campo) }}</textarea>
                            @error($campo)
                                <p class="error-campo">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </section>
        @endunless

        {{-- Datos de la consulta --}}
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Datos de la consulta</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-3">
                    <label for="motivo" class="etiqueta">Motivo <span class="text-red-500">*</span></label>
                    <input id="motivo" name="motivo" type="text" required maxlength="255" class="campo"
                        value="{{ old('motivo', $cita?->motivo) }}">
                    @error('motivo')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="peso_kg" class="etiqueta">Peso (kg)</label>
                    <input id="peso_kg" name="peso_kg" type="number" step="0.01" min="0.01" max="2000"
                        class="campo" value="{{ old('peso_kg') }}">
                    @error('peso_kg')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="temperatura_c" class="etiqueta">Temperatura (°C)</label>
                    <input id="temperatura_c" name="temperatura_c" type="number" step="0.1" min="30" max="45"
                        class="campo" value="{{ old('temperatura_c') }}">
                    @error('temperatura_c')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-3">
                    <label for="diagnostico" class="etiqueta">Diagnóstico <span class="text-red-500">*</span></label>
                    <textarea id="diagnostico" name="diagnostico" rows="3" required maxlength="5000"
                        class="campo">{{ old('diagnostico') }}</textarea>
                    @error('diagnostico')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-3">
                    <label for="tratamiento_indicado" class="etiqueta">Tratamiento indicado</label>
                    <textarea id="tratamiento_indicado" name="tratamiento_indicado" rows="2" maxlength="5000"
                        placeholder="Indicaciones generales: reposo, dieta, cuidados..."
                        class="campo">{{ old('tratamiento_indicado') }}</textarea>
                    @error('tratamiento_indicado')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-3">
                    <label for="observaciones" class="etiqueta">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="2" maxlength="5000"
                        class="campo">{{ old('observaciones') }}</textarea>
                    @error('observaciones')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        {{-- Medicamentos recetados --}}
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Medicamentos recetados</h2>
                    <p class="mt-1 text-xs text-gray-500">Opcional. Las filas vacías no se guardan.</p>
                </div>
                <button type="button" class="btn-secundario" data-agregar-medicamento>+ Agregar medicamento</button>
            </div>

            <div id="lista-medicamentos" class="mt-4 space-y-4" data-siguiente="{{ count($filas) }}">
                @foreach ($filas as $i => $fila)
                    @include('consultas._medicamento', ['i' => $i, 'fila' => $fila])
                @endforeach
            </div>

            <template id="plantilla-medicamento">
                @include('consultas._medicamento', ['i' => '__I__', 'fila' => []])
            </template>
        </section>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-end">
            <a href="{{ route('expedientes.show', $mascota) }}" class="btn-secundario">Cancelar</a>
            <button type="submit" class="btn-primario">Guardar consulta</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Agrega y quita filas de medicamentos
        (() => {
            const lista = document.getElementById('lista-medicamentos');
            const plantilla = document.getElementById('plantilla-medicamento');
            let siguiente = Number(lista.dataset.siguiente);

            document.querySelector('[data-agregar-medicamento]').addEventListener('click', () => {
                const html = plantilla.innerHTML.replaceAll('__I__', siguiente++);
                lista.insertAdjacentHTML('beforeend', html);
                lista.lastElementChild.querySelector('input').focus();
            });

            lista.addEventListener('click', (evento) => {
                const boton = evento.target.closest('[data-quitar-medicamento]');
                if (!boton) return;

                const filas = lista.querySelectorAll('[data-medicamento]');
                if (filas.length === 1) {
                    // Si es la única fila, solo se vacía
                    filas[0].querySelectorAll('input, textarea').forEach((campo) => { campo.value = ''; });
                    return;
                }
                boton.closest('[data-medicamento]').remove();
            });
        })();
    </script>
@endpush
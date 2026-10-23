@extends('layouts.app')

@section('titulo', 'Citas')

@section('contenido')
    @php
        // Estados de una cita y su color en el calendario
        $estados = [
            'pendiente' => ['nombre' => 'Pendiente', 'color' => '#f59e0b'],
            'atendida' => ['nombre' => 'Atendida', 'color' => '#0f766e'],
            'cancelada' => ['nombre' => 'Cancelada', 'color' => '#dc2626'],
            'no_asistio' => ['nombre' => 'No asistió', 'color' => '#6b7280'],
        ];

        // Si el servidor rechazó una edición, el formulario se reabre en modo edición
        $citaEditando = old('_formulario') === 'cita' ? old('_cita_id') : null;
    @endphp

    {{-- Mensaje de éxito que envía el controlador (registrar, actualizar, cancelar, cambiar estado) --}}
    @if (session('status'))
        <div role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Errores del servidor, por ejemplo un choque de horario --}}
    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
            <p class="font-semibold">No se pudo completar la operación:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_18rem]">

        {{-- Calendario --}}
        <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            {{-- $citas lo envía CitaController. Cada cita: id, start, end, estado, mascota, motivo, cliente, telefono, veterinario --}}
            <div id="calendario-citas" data-citas='@json($citas ?? [])' data-estados='@json($estados)'></div>
        </div>

        <aside class="space-y-6">
            {{-- Leyenda de estados --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-800">Estados de la cita</h2>
                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                    @foreach ($estados as $estado)
                        <li class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $estado['color'] }}"></span>
                            {{ $estado['nombre'] }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Horario seleccionado en el calendario --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-800">Horario seleccionado</h2>
                <p id="fecha-seleccionada" class="mt-3 text-sm text-gray-500">
                    Haz clic en un horario del calendario.
                </p>
            </div>

            {{-- Acceso a los recordatorios del día siguiente --}}
            <a href="{{ route('citas.recordatorios') }}" class="btn-secundario block w-full text-center">
                Ver recordatorios de mañana
            </a>
        </aside>
    </div>

    {{-- Ventana emergente con el detalle de la cita y sus acciones --}}
    <div id="modal-cita" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-cita-titulo">
        <div class="w-full max-w-md rounded-2xl bg-white shadow-xl">

            <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Detalle de la cita</p>
                    <h2 id="modal-cita-titulo" class="mt-0.5 text-base font-semibold text-gray-800" data-campo="mascota"></h2>
                </div>

                <button type="button" data-cerrar-modal aria-label="Cerrar"
                    class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <dl class="space-y-3 px-5 py-4 text-sm">
                @foreach ([
                    'motivo' => 'Motivo',
                    'horario' => 'Horario',
                    'cliente' => 'Propietario',
                    'telefono' => 'Teléfono',
                    'veterinario' => 'Veterinario',
                ] as $campo => $etiqueta)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $etiqueta }}</dt>
                        <dd class="mt-0.5 text-gray-800" data-campo="{{ $campo }}"></dd>
                    </div>
                @endforeach

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Estado</dt>
                    <dd class="mt-1">
                        <span data-campo="estado" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium text-white"></span>
                    </dd>
                </div>
            </dl>

            {{-- Acciones sobre la cita: el JavaScript completa la dirección de cada formulario --}}
            <div class="space-y-3 border-t border-gray-100 px-5 py-4">
                <form id="form-estado-cita" method="POST" class="flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <label for="estado-cita" class="sr-only">Nuevo estado</label>
                    <select id="estado-cita" name="estado" class="campo">
                        @foreach ($estados as $clave => $estado)
                            @continue($clave === 'cancelada')
                            <option value="{{ $clave }}">{{ $estado['nombre'] }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secundario shrink-0">Cambiar estado</button>
                </form>

                <div class="flex justify-between gap-3">
                    <form id="form-cancelar-cita" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            Cancelar cita
                        </button>
                    </form>

                    <button type="button" data-editar-cita class="btn-primario">Editar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Ventana emergente para registrar o editar una cita --}}
    {{-- $mascotas (id, nombre, cliente, telefono) y $veterinarios (id, nombre) los envía CitaController --}}
    <div id="modal-nueva-cita" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-nueva-cita-titulo">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl">

            <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <p id="modal-nueva-cita-etiqueta" class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        {{ $citaEditando ? 'Editar cita' : 'Nueva cita' }}
                    </p>
                    <h2 id="modal-nueva-cita-titulo" class="mt-0.5 text-base font-semibold text-gray-800">
                        {{ $citaEditando ? 'Modificar cita' : 'Agendar cita' }}
                    </h2>
                </div>

                <button type="button" data-cerrar-modal aria-label="Cerrar"
                    class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-nueva-cita" method="POST" class="space-y-4 px-5 py-4"
                action="{{ $citaEditando ? url('/citas/' . $citaEditando) : url('/citas') }}"
                data-url-citas="{{ url('/citas') }}">
                @csrf

                {{-- Al editar se activa el método PUT; al crear queda desactivado y se envía como POST --}}
                <input type="hidden" id="metodo-cita" name="_method" value="PUT" @disabled(! $citaEditando)>
                <input type="hidden" id="cita-id" name="_cita_id" value="{{ $citaEditando }}">
                <input type="hidden" name="_formulario" value="cita">

                {{-- Resumen de errores dentro del formulario, para que se vea sin cerrar la ventana --}}
                @if ($errors->any() && old('_formulario') === 'cita')
                    <div role="alert" class="rounded-lg border border-red-500/30 bg-red-50 px-4 py-3 text-sm text-red-600">
                        Revisa los datos marcados antes de guardar.
                    </div>
                @endif

                <div>
                    <label for="mascota_id" class="etiqueta">Mascota</label>
                    <select id="mascota_id" name="mascota_id" class="campo" required>
                        <option value="">Selecciona una mascota</option>
                        @foreach ($mascotas ?? [] as $mascota)
                            <option value="{{ $mascota['id'] }}" @selected(old('mascota_id') == $mascota['id'])>
                                {{ $mascota['nombre'] }} — {{ $mascota['cliente'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('mascota_id')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="veterinario_id" class="etiqueta">Veterinario</label>
                    <select id="veterinario_id" name="veterinario_id" class="campo" required>
                        <option value="">Selecciona un veterinario</option>
                        @foreach ($veterinarios ?? [] as $veterinario)
                            <option value="{{ $veterinario['id'] }}" @selected(old('veterinario_id') == $veterinario['id'])>
                                {{ $veterinario['nombre'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('veterinario_id')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_cita" class="etiqueta">Fecha y hora</label>
                    <input type="datetime-local" id="fecha_cita" name="fecha_cita" class="campo" step="1800" required
                        value="{{ old('fecha_cita') }}">
                    @error('fecha_cita')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="motivo" class="etiqueta">Motivo</label>
                    <input type="text" id="motivo" name="motivo" class="campo" maxlength="255"
                        placeholder="Ej. Vacunación" required value="{{ old('motivo') }}">
                    @error('motivo')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="observaciones" class="etiqueta">
                        Observaciones <span class="font-normal text-gray-400">(opcional)</span>
                    </label>
                    <textarea id="observaciones" name="observaciones" rows="3" class="campo">{{ old('observaciones') }}</textarea>
                    @error('observaciones')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                    <button type="button" data-cerrar-modal class="btn-secundario">Cancelar</button>
                    <button type="submit" id="boton-guardar-cita" class="btn-primario">
                        {{ $citaEditando ? 'Guardar cambios' : 'Guardar cita' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@if ($errors->any() && old('_formulario') === 'cita')
    @push('scripts')
        <script>
            // Si el servidor rechazó la cita, se vuelve a abrir el formulario con los datos escritos
            (() => {
                const modal = document.getElementById('modal-nueva-cita');

                if (!modal) return;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            })();
        </script>
    @endpush
@endif
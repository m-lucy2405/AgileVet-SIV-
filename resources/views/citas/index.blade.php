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

       
    @endphp

    <div class="grid gap-6 xl:grid-cols-[1fr_18rem]">

        {{-- Calendario --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
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
         </aside>
    </div>

    {{-- Ventana emergente con el detalle de la cita --}}
    <div id="modal-cita" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-cita-titulo">
        <div class="w-full max-w-sm rounded-2xl bg-white shadow-xl">

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
        </div>
    </div>

        {{-- Ventana emergente para registrar una cita --}}
    {{-- $mascotas (id, nombre, cliente, telefono) y $veterinarios (id, nombre) los envía CitaController --}}
    <div id="modal-nueva-cita" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4"
        role="dialog" aria-modal="true" aria-labelledby="modal-nueva-cita-titulo">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl">

            <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Nueva cita</p>
                    <h2 id="modal-nueva-cita-titulo" class="mt-0.5 text-base font-semibold text-gray-800">Agendar cita</h2>
                </div>

                <button type="button" data-cerrar-modal aria-label="Cerrar"
                    class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-nueva-cita" action="#" method="POST" class="space-y-4 px-5 py-4">
                @csrf
                <input type="hidden" name="estado" value="pendiente">

                <div>
                    <label for="mascota_id" class="etiqueta">Mascota</label>
                    <select id="mascota_id" name="mascota_id" class="campo" required>
                        <option value="">Selecciona una mascota</option>
                        @foreach ($mascotas ?? [] as $mascota)
                            <option value="{{ $mascota['id'] }}" data-nombre="{{ $mascota['nombre'] }}"
                                data-cliente="{{ $mascota['cliente'] }}" data-telefono="{{ $mascota['telefono'] }}">
                                {{ $mascota['nombre'] }} — {{ $mascota['cliente'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="usuario_id" class="etiqueta">Veterinario</label>
                    <select id="usuario_id" name="usuario_id" class="campo" required>
                        <option value="">Selecciona un veterinario</option>
                        @foreach ($veterinarios ?? [] as $veterinario)
                            <option value="{{ $veterinario['id'] }}">{{ $veterinario['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fecha_hora" class="etiqueta">Fecha y hora</label>
                    <input type="datetime-local" id="fecha_hora" name="fecha_hora" class="campo" step="1800" required>
                </div>

                <div>
                    <label for="motivo" class="etiqueta">Motivo</label>
                    <input type="text" id="motivo" name="motivo" class="campo" maxlength="255"
                        placeholder="Ej. Vacunación" required>
                </div>

                <div>
                    <label for="observaciones" class="etiqueta">
                        Observaciones <span class="font-normal text-gray-400">(opcional)</span>
                    </label>
                    <textarea id="observaciones" name="observaciones" rows="3" class="campo"></textarea>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                    <button type="button" data-cerrar-modal class="btn-secundario">Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar cita</button>
                </div>
            </form>
        </div>
    </div>
@endsection
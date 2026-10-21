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

        // Citas de ejemplo para la maquetación.
        // Se reemplazan por las citas reales cuando exista el controlador.
        $hoy = now()->startOfDay();

        $citasEjemplo = [
            ['dia' => -1, 'hora' => '09:30', 'minutos' => 30, 'mascota' => 'Luna', 'motivo' => 'Control general', 'cliente' => 'María López', 'telefono' => '7123-4567', 'veterinario' => 'Dr. Juan Veterinario', 'estado' => 'atendida'],
            ['dia' => -1, 'hora' => '16:00', 'minutos' => 30, 'mascota' => 'Rocky', 'motivo' => 'Desparasitación', 'cliente' => 'José Martínez', 'telefono' => '7890-1234', 'veterinario' => 'Dr. Juan Veterinario', 'estado' => 'no_asistio'],
            ['dia' => 0, 'hora' => '08:00', 'minutos' => 30, 'mascota' => 'Firulais', 'motivo' => 'Vacunación antirrábica', 'cliente' => 'Ana Rivera', 'telefono' => '7012-3456', 'veterinario' => 'Dr. Juan Veterinario', 'estado' => 'pendiente'],
            ['dia' => 0, 'hora' => '10:30', 'minutos' => 60, 'mascota' => 'Michi', 'motivo' => 'Esterilización', 'cliente' => 'Carlos Hernández', 'telefono' => '7234-5678', 'veterinario' => 'Dr. Juan Veterinario', 'estado' => 'pendiente'],
            ['dia' => 0, 'hora' => '14:00', 'minutos' => 30, 'mascota' => 'Toby', 'motivo' => 'Revisión de herida', 'cliente' => 'Luis Gómez', 'telefono' => '7456-7890', 'veterinario' => 'Dr. Juan Veterinario', 'estado' => 'pendiente'],
            ['dia' => 1, 'hora' => '09:00', 'minutos' => 30, 'mascota' => 'Canela', 'motivo' => 'Vacuna triple felina', 'cliente' => 'Sofía Ramírez', 'telefono' => '7567-8901', 'veterinario' => 'Dr. Juan Veterinario', 'estado' => 'pendiente'],
            ['dia' => 1, 'hora' => '11:00', 'minutos' => 30, 'mascota' => 'Max', 'motivo' => 'Consulta por vómito', 'cliente' => 'Pedro Castillo', 'telefono' => '7678-9012', 'veterinario' => 'Dr. Juan Veterinario', 'estado' => 'pendiente'],
            ['dia' => 2, 'hora' => '15:00', 'minutos' => 45, 'mascota' => 'Lola', 'motivo' => 'Baño y corte', 'cliente' => 'Elena Flores', 'telefono' => '7789-0123', 'veterinario' => 'Dr. Juan Veterinario', 'estado' => 'cancelada'],
        ];

        // Convierte cada cita al formato que usa FullCalendar
        $eventos = array_map(function (array $cita) use ($hoy, $estados) {
            [$hora, $minuto] = explode(':', $cita['hora']);
            $inicio = $hoy->copy()->addDays($cita['dia'])->setTime((int) $hora, (int) $minuto);
            $color = $estados[$cita['estado']]['color'];

            return [
                'title' => "{$cita['mascota']} · {$cita['motivo']}",
                'start' => $inicio->format('Y-m-d\TH:i:s'),
                'end' => $inicio->copy()->addMinutes($cita['minutos'])->format('Y-m-d\TH:i:s'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'mascota' => $cita['mascota'],
                    'motivo' => $cita['motivo'],
                    'cliente' => $cita['cliente'],
                    'telefono' => $cita['telefono'],
                    'veterinario' => $cita['veterinario'],
                    'estado' => $estados[$cita['estado']]['nombre'],
                ],
            ];
        }, $citasEjemplo);
    @endphp

    <div class="grid gap-6 xl:grid-cols-[1fr_18rem]">

        {{-- Calendario --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <div id="calendario-citas" data-citas='@json($eventos)'></div>
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
@endsection
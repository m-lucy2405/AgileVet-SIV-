@extends('layouts.app')

@section('titulo', 'Expediente de ' . $mascota->nombre)

@section('contenido')
    <a href="{{ route('expedientes.index') }}" class="mb-4 inline-block text-sm text-primario-300 hover:underline">
        &larr; Volver a expedientes
    </a>

    {{-- Datos de la mascota y del propietario --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">{{ $mascota->nombre }}</h2>
            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Especie</dt>
                    <dd class="text-gray-800">{{ $mascota->especie }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Raza</dt>
                    <dd class="text-gray-800">{{ $mascota->raza ?: 'No indicada' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Sexo</dt>
                    <dd class="text-gray-800">{{ ucfirst($mascota->sexo) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Edad</dt>
                    <dd class="text-gray-800">
                        @if ($mascota->fecha_nacimiento)
                            {{ $mascota->fecha_nacimiento->age }} {{ $mascota->fecha_nacimiento->age === 1 ? 'año' : 'años' }}
                        @else
                            No indicada
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Color</dt>
                    <dd class="text-gray-800">{{ $mascota->color ?: 'No indicado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Esterilizado(a)</dt>
                    <dd class="text-gray-800">{{ $mascota->esterilizado ? 'Sí' : 'No' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Propietario</h2>
            <dl class="mt-3 space-y-3 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Nombre</dt>
                    <dd class="text-gray-800">{{ $mascota->cliente->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Teléfono</dt>
                    <dd class="text-gray-800">{{ $mascota->cliente->telefono }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Dirección</dt>
                    <dd class="text-gray-800">{{ $mascota->cliente->direccion ?: 'No indicada' }}</dd>
                </div>
            </dl>
        </section>
    </div>

    @if (! $expediente)
        {{-- Mascota sin expediente todavía --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
            Esta mascota todavía no tiene expediente clínico. Se abrirá automáticamente al registrar su primera consulta.
        </div>
    @else
        {{-- Alergias: destacadas para que nadie las pase por alto --}}
        @if ($expediente->alergias)
            <div role="alert" class="mb-6 rounded-xl border border-red-500/40 bg-red-50 px-5 py-4 text-sm text-red-600">
                <p class="font-semibold uppercase tracking-wide">Alergias</p>
                <p class="mt-1 whitespace-pre-line">{{ $expediente->alergias }}</p>
            </div>
        @endif

        {{-- Datos generales del expediente --}}
        <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Expediente clínico</h2>
            <p class="mt-1 text-xs text-gray-500">Abierto el {{ $expediente->fecha_apertura->format('d/m/Y') }}</p>

            <dl class="mt-4 grid gap-4 text-sm md:grid-cols-3">
                @foreach ([
                    'antecedentes' => 'Antecedentes',
                    'enfermedades_cronicas' => 'Enfermedades crónicas',
                    'observaciones' => 'Observaciones',
                ] as $campo => $etiqueta)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">{{ $etiqueta }}</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-gray-800">{{ $expediente->$campo ?: 'Sin registros' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- Próximas citas --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-gray-800">Próximas citas</h2>
        @forelse ($proximasCitas as $cita)
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                <span class="text-gray-800">{{ $cita->fecha_cita->format('d/m/Y H:i') }} · {{ $cita->motivo }}</span>
                <span class="text-gray-500">{{ $cita->veterinario->nombre }}</span>
            </div>
        @empty
            <p class="mt-3 text-sm text-gray-500">No tiene citas pendientes.</p>
        @endforelse
    </section>

    @if ($expediente)
        {{-- Historial de consultas --}}
        <section class="mb-6">
            <h2 class="mb-3 text-base font-semibold text-gray-800">Consultas</h2>

            @forelse ($expediente->consultas as $consulta)
                <article class="mb-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="font-semibold text-gray-800">{{ $consulta->motivo }}</h3>
                        <span class="text-xs text-gray-500">
                            {{ $consulta->fecha_consulta->format('d/m/Y H:i') }} · {{ $consulta->veterinario->nombre }}
                        </span>
                    </div>

                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Peso</dt>
                            <dd class="text-gray-800">{{ $consulta->peso_kg ? $consulta->peso_kg . ' kg' : 'No registrado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Temperatura</dt>
                            <dd class="text-gray-800">{{ $consulta->temperatura_c ? $consulta->temperatura_c . ' °C' : 'No registrada' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Diagnóstico</dt>
                            <dd class="whitespace-pre-line text-gray-800">{{ $consulta->diagnostico }}</dd>
                        </div>
                        @if ($consulta->tratamiento_indicado)
                            <div class="sm:col-span-2">
                                <dt class="text-xs uppercase tracking-wide text-gray-500">Tratamiento indicado</dt>
                                <dd class="whitespace-pre-line text-gray-800">{{ $consulta->tratamiento_indicado }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($consulta->tratamientos->isNotEmpty())
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[520px] text-left text-sm">
                                <thead class="text-xs uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="py-2 pr-3 font-semibold">Medicamento</th>
                                        <th class="py-2 pr-3 font-semibold">Dosis</th>
                                        <th class="py-2 pr-3 font-semibold">Frecuencia</th>
                                        <th class="py-2 font-semibold">Duración</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($consulta->tratamientos as $tratamiento)
                                        <tr>
                                            <td class="py-2 pr-3 text-gray-800">{{ $tratamiento->medicamento }}</td>
                                            <td class="py-2 pr-3 text-gray-600">{{ $tratamiento->dosis }}</td>
                                            <td class="py-2 pr-3 text-gray-600">{{ $tratamiento->frecuencia }}</td>
                                            <td class="py-2 text-gray-600">{{ $tratamiento->duracion }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </article>
            @empty
                <p class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-500 shadow-sm">
                    Todavía no hay consultas registradas.
                </p>
            @endforelse
        </section>

        {{-- Vacunas y desparasitaciones --}}
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ([
                ['titulo' => 'Vacunas', 'registros' => $expediente->vacunaciones, 'nombre' => 'nombre_vacuna', 'proxima' => 'proxima_dosis'],
                ['titulo' => 'Desparasitaciones', 'registros' => $expediente->desparasitaciones, 'nombre' => 'producto', 'proxima' => 'proxima_aplicacion'],
            ] as $bloque)
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-gray-800">{{ $bloque['titulo'] }}</h2>

                    @forelse ($bloque['registros'] as $registro)
                        @php $proxima = $registro->{$bloque['proxima']}; @endphp
                        <div class="mt-3 flex flex-wrap items-baseline justify-between gap-2 text-sm">
                            <span class="text-gray-800">{{ $registro->{$bloque['nombre']} }}</span>
                            <span class="text-xs text-gray-500">
                                Aplicada el {{ $registro->fecha_aplicacion->format('d/m/Y') }}
                                @if ($proxima)
                                    · Próxima:
                                    <span class="{{ $proxima->isPast() ? 'font-semibold text-red-600' : 'text-gray-700' }}">
                                        {{ $proxima->format('d/m/Y') }}{{ $proxima->isPast() ? ' (vencida)' : '' }}
                                    </span>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-gray-500">Sin registros.</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    @endif
@endsection
@extends('layouts.app')

@section('titulo', 'Expediente de ' . $mascota->nombre)

@section('contenido')
    @php
        $esVeterinario = auth()->user()?->role?->nombre === 'Veterinario';
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('expedientes.index') }}" class="text-sm text-primario-300 hover:underline">
            &larr; Volver a expedientes
        </a>

        @if ($esVeterinario)
            <a href="{{ route('consultas.create', $mascota) }}" class="btn-primario">+ Registrar consulta</a>
        @endif
    </div>

    {{-- Mensaje de éxito (al registrar, corregir o editar información del expediente) --}}
    @if (session('status'))
        <div role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

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
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
            Esta mascota todavía no tiene expediente clínico. Se abrirá automáticamente al registrar su primera
            consulta, vacuna o desparasitación.
            @if ($esVeterinario)
                <div class="mt-3 flex flex-wrap gap-3">
                    <a href="{{ route('expedientes.edit', $mascota) }}" class="btn-secundario">Abrir expediente</a>
                    <a href="{{ route('vacunaciones.create', $mascota) }}" class="btn-secundario">+ Registrar vacuna</a>
                    <a href="{{ route('desparasitaciones.create', $mascota) }}" class="btn-secundario">+ Registrar desparasitación</a>
                </div>
            @endif
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
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Expediente clínico</h2>
                    <p class="mt-1 text-xs text-gray-500">Abierto el {{ $expediente->fecha_apertura->format('d/m/Y') }}</p>
                </div>
                @if ($esVeterinario)
                    <a href="{{ route('expedientes.edit', $mascota) }}"
                        class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primario-300 transition hover:bg-primario-500/10">
                        Editar datos
                    </a>
                @endif
            </div>

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
                <span class="flex items-center gap-3">
                    <span class="text-gray-500">{{ $cita->veterinario->nombre }}</span>
                    @if ($esVeterinario)
                        <a href="{{ route('consultas.create', ['mascota' => $mascota, 'cita' => $cita->id]) }}"
                            class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primario-300 transition hover:bg-primario-500/10">
                            Atender
                        </a>
                    @endif
                </span>
            </div>
        @empty
            <p class="mt-3 text-sm text-gray-500">No tiene citas pendientes.</p>
        @endforelse
    </section>

    @if ($expediente)
        {{-- Consulta más reciente --}}
        <section class="mb-6">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Consultas ({{ $totalConsultas }})</h2>
                    @if ($totalConsultas > $consultas->count())
                        <p class="text-xs text-gray-500">
                            {{ $consultas->count() === 1 ? 'Se muestra la más reciente.' : 'Se muestran las ' . $consultas->count() . ' más recientes.' }}
                        </p>
                    @endif
                </div>

                @if ($totalConsultas > 0)
                    <a href="{{ route('expedientes.consultas', $mascota) }}" class="btn-secundario">
                        Ver historial completo
                    </a>
                @endif
            </div>

            @forelse ($consultas as $consulta)
                @include('consultas._tarjeta', ['consulta' => $consulta])
            @empty
                <p class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-500 shadow-sm">
                    Todavía no hay consultas registradas.
                </p>
            @endforelse
        </section>

        {{-- Vacunas y desparasitaciones --}}
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ([
                ['titulo' => 'Vacunas', 'registros' => $expediente->vacunaciones, 'nombre' => 'nombre_vacuna', 'proxima' => 'proxima_dosis', 'ruta' => 'vacunaciones.create'],
                ['titulo' => 'Desparasitaciones', 'registros' => $expediente->desparasitaciones, 'nombre' => 'producto', 'proxima' => 'proxima_aplicacion', 'ruta' => 'desparasitaciones.create'],
            ] as $bloque)
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-gray-800">{{ $bloque['titulo'] }}</h2>
                        @if ($esVeterinario)
                            <a href="{{ route($bloque['ruta'], $mascota) }}"
                                class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primario-300 transition hover:bg-primario-500/10">
                                + Registrar
                            </a>
                        @endif
                    </div>

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
@extends('layouts.app')

@section('titulo', 'Ficha del cliente')

@section('contenido')
    <div class="mx-auto max-w-5xl space-y-6">
        <a href="{{ route('clientes.index') }}" class="text-sm text-gray-500 hover:underline">← Volver a clientes</a>

        @if (session('success'))
            <div class="rounded-lg border border-[#4ade80] bg-[#1f4d3a] px-4 py-3 text-sm font-medium text-[#dcfce7]">
                {{ session('success') }}
            </div>
        @endif

        {{-- Datos del propietario --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-start justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-800">{{ $cliente->nombre }}</h2>
                <a href="{{ route('clientes.edit', $cliente) }}" class="btn-secundario">Editar</a>
            </div>

            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="etiqueta">Teléfono</dt>
                    <dd class="text-gray-800">{{ $cliente->telefono }}</dd>
                </div>
                <div>
                    <dt class="etiqueta">Correo</dt>
                    <dd class="text-gray-800">{{ $cliente->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="etiqueta">Dirección</dt>
                    <dd class="text-gray-800">{{ $cliente->direccion }}</dd>
                </div>
            </dl>
        </section>

        {{-- Mascotas del cliente --}}
        <section>
            <h2 class="mb-3 text-base font-semibold text-gray-800">Mascotas ({{ $cliente->mascotas->count() }})</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($cliente->mascotas as $mascota)
                    <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="font-semibold text-gray-800">{{ $mascota->nombre }}</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $mascota->especie }}{{ $mascota->raza ? ' · ' . $mascota->raza : '' }} · {{ ucfirst($mascota->sexo) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $mascota->color ?: 'Sin color registrado' }} ·
                            {{ $mascota->esterilizado ? 'Esterilizado(a)' : 'No esterilizado(a)' }}
                            @if ($mascota->fecha_nacimiento)
                                · Nació el {{ $mascota->fecha_nacimiento->format('d/m/Y') }}
                            @endif
                        </p>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('expedientes.show', $mascota) }}" class="btn-primario">Ver expediente</a>
                            <a href="{{ route('mascotas.edit', $mascota) }}" class="btn-secundario">Editar</a>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-gray-500">Este cliente no tiene mascotas registradas.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['titulo' => 'Citas de hoy', 'valor' => '—', 'color' => '#22D3EE'],
            ['titulo' => 'Pacientes registrados', 'valor' => '—', 'color' => '#A78BFA'],
            ['titulo' => 'Productos con stock bajo', 'valor' => '—', 'color' => '#FB923C'],
            ['titulo' => 'Ventas del día', 'valor' => '—', 'color' => '#34D399'],
        ] as $tarjeta)
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                style="--acento: {{ $tarjeta['color'] }}">
                {{-- Línea de color en la parte superior --}}
                <span aria-hidden="true" class="absolute inset-x-0 top-0 h-0.5"
                    style="background: linear-gradient(90deg, var(--acento), transparent); box-shadow: 0 0 12px var(--acento);"></span>

                <p class="text-sm text-gray-500">{{ $tarjeta['titulo'] }}</p>
                <p class="mt-2 text-3xl font-semibold" style="color: var(--acento); text-shadow: 0 0 18px color-mix(in srgb, var(--acento) 35%, transparent);">
                    {{ $tarjeta['valor'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-gray-800">Bienvenido al sistema</h2>
        <p class="mt-1 text-sm text-gray-600">
            Desde el menú lateral puedes acceder a la gestión de citas, clientes, expedientes e inventario.
        </p>
    </div>
@endsection
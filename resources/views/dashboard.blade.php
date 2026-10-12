@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['titulo' => 'Citas de hoy', 'valor' => '—'],
            ['titulo' => 'Pacientes registrados', 'valor' => '—'],
            ['titulo' => 'Productos con stock bajo', 'valor' => '—'],
            ['titulo' => 'Ventas del día', 'valor' => '—'],
        ] as $tarjeta)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">{{ $tarjeta['titulo'] }}</p>
                <p class="mt-2 text-3xl font-semibold text-primario-700">{{ $tarjeta['valor'] }}</p>
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
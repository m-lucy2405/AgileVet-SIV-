@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    @php
        // El enlace a ventas solo se muestra si existe la ruta (la crearon en el módulo de Ventas)
        $rutaVentas = \Illuminate\Support\Facades\Route::has('ventas.index') ? route('ventas.index') : null;

        $tarjetas = [
            [
                'titulo' => $esVeterinario ? 'Tus citas de hoy' : 'Citas de hoy',
                'valor' => $citasHoy,
                'detalle' => $citasPendientesHoy === 1 ? '1 pendiente' : "{$citasPendientesHoy} pendientes",
                'color' => '#22D3EE',
                'url' => route('citas.index'),
            ],
            [
                'titulo' => 'Pacientes registrados',
                'valor' => $mascotas,
                'detalle' => $clientes === 1 ? 'de 1 cliente' : "de {$clientes} clientes",
                'color' => '#A78BFA',
                'url' => route('expedientes.index'),
            ],
            [
                'titulo' => 'Productos con stock bajo',
                'valor' => $stockBajo,
                'detalle' => $stockBajo === 0 ? 'Todo en orden' : 'Hay que reponer',
                'color' => '#FB923C',
                'url' => route('productos.index', ['alerta' => 'stock_bajo']),
            ],
            [
                'titulo' => 'Ventas del día',
                'valor' => '$ ' . number_format($ventasHoyTotal, 2),
                'detalle' => $ventasHoyCantidad === 1 ? '1 venta' : "{$ventasHoyCantidad} ventas",
                'color' => '#34D399',
                'url' => $rutaVentas,
            ],
        ];
    @endphp

    {{-- Indicadores principales --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tarjetas as $tarjeta)
            @php $etiqueta = $tarjeta['url'] ? 'a' : 'div'; @endphp
            <{{ $etiqueta }} @if ($tarjeta['url']) href="{{ $tarjeta['url'] }}" @endif
                class="relative block overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition {{ $tarjeta['url'] ? 'hover:border-primario-500' : '' }}"
                style="--acento: {{ $tarjeta['color'] }}">
                {{-- Línea de color en la parte superior --}}
                <span aria-hidden="true" class="absolute inset-x-0 top-0 h-0.5"
                    style="background: linear-gradient(90deg, var(--acento), transparent); box-shadow: 0 0 12px var(--acento);"></span>

                <p class="text-sm text-gray-500">{{ $tarjeta['titulo'] }}</p>
                <p class="mt-2 text-3xl font-semibold" style="color: var(--acento); text-shadow: 0 0 18px color-mix(in srgb, var(--acento) 35%, transparent);">
                    {{ $tarjeta['valor'] }}
                </p>
                <p class="mt-1 text-xs text-gray-500">{{ $tarjeta['detalle'] }}</p>
            </{{ $etiqueta }}>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Próximas citas de hoy --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ $esVeterinario ? 'Tus próximas citas de hoy' : 'Próximas citas de hoy' }}
                </h2>
                <a href="{{ route('citas.index') }}" class="text-sm text-primario-300 hover:underline">Ver agenda</a>
            </div>

            @forelse ($proximasCitas as $cita)
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3 text-sm first-of-type:border-t-0">
                    <span class="flex items-center gap-3">
                        <span class="font-semibold text-gray-800">{{ $cita->fecha_cita->format('H:i') }}</span>
                        <a href="{{ route('expedientes.show', $cita->mascota) }}" class="text-gray-800 hover:text-primario-300 hover:underline">
                            {{ $cita->mascota->nombre }}
                        </a>
                        <span class="text-gray-500">· {{ $cita->motivo }}</span>
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ $cita->mascota->cliente->nombre }}
                        @unless ($esVeterinario)
                            · {{ $cita->veterinario->nombre }}
                        @endunless
                    </span>
                </div>
            @empty
                <p class="mt-3 text-sm text-gray-500">No hay más citas pendientes para hoy.</p>
            @endforelse
        </section>

        {{-- Alertas --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Alertas</h2>

            <ul class="mt-3 space-y-3 text-sm">
                <li>
                    <a href="{{ route('productos.index', ['alerta' => 'por_vencer']) }}" class="flex items-center justify-between gap-3 hover:underline">
                        <span class="text-gray-600">Productos vencidos o por vencer ({{ $diasVencimiento }} días)</span>
                        <span class="font-semibold {{ $porVencer > 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $porVencer }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reportes.dosis', ['dias' => $diasDosis]) }}" class="flex items-center justify-between gap-3 hover:underline">
                        <span class="text-gray-600">Vacunas y desparasitaciones ({{ $diasDosis }} días)</span>
                        <span class="font-semibold {{ $dosisProximas > 0 ? 'text-amber-300' : 'text-gray-800' }}">{{ $dosisProximas }}</span>
                    </a>
                </li>
                <li class="flex items-center justify-between gap-3">
                    <span class="text-gray-600">{{ $esVeterinario ? 'Tus consultas del mes' : 'Consultas del mes' }}</span>
                    <span class="font-semibold text-gray-800">{{ $consultasMes }}</span>
                </li>
            </ul>
        </section>
    </div>
@endsection
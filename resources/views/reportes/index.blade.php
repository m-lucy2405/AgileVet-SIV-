@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
    @php
        $reportes = [];

        if ($puedeVerGestion) {
            $reportes[] = ['ruta' => 'reportes.citas', 'titulo' => 'Citas', 'descripcion' => 'Citas por estado y por veterinario, y porcentaje de asistencia.'];
            $reportes[] = ['ruta' => 'reportes.consultas', 'titulo' => 'Consultas por veterinario', 'descripcion' => 'Cuántas consultas atendió cada veterinario en el período.'];
            $reportes[] = ['ruta' => 'reportes.diagnosticos', 'titulo' => 'Diagnósticos más frecuentes', 'descripcion' => 'Los 10 diagnósticos que más se repiten.'];
        }

        if ($puedeVerDosis) {
            $reportes[] = ['ruta' => 'reportes.dosis', 'titulo' => 'Próximas dosis', 'descripcion' => 'Vacunas y desparasitaciones por vencer o vencidas, con el teléfono del dueño.'];
        }
    @endphp

    <p class="mb-6 text-sm text-gray-600">Elige el reporte que quieres consultar.</p>

    @if ($reportes === [])
        <p class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-500 shadow-sm">
            No tienes reportes disponibles para tu rol.
        </p>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($reportes as $reporte)
                <a href="{{ route($reporte['ruta']) }}"
                    class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primario-500">
                    <h2 class="text-base font-semibold text-gray-800">{{ $reporte['titulo'] }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $reporte['descripcion'] }}</p>
                </a>
            @endforeach
        </div>
    @endif
@endsection
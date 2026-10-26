@extends('layouts.app')

@section('titulo', 'Próximas dosis')

@section('contenido')
    @include('reportes._filtros', ['ruta' => 'reportes.dosis'])

    {{-- Filtros propios de este reporte --}}
    <form method="GET" action="{{ route('reportes.dosis') }}"
        class="no-imprimir mb-6 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end">
        <div>
            <label for="estado" class="etiqueta">Mostrar</label>
            <select id="estado" name="estado" class="campo">
                <option value="proximas" @selected($estado === 'proximas')>Por vencer</option>
                <option value="vencidas" @selected($estado === 'vencidas')>Vencidas</option>
            </select>
        </div>
        <div>
            <label for="dias" class="etiqueta">En los próximos</label>
            <select id="dias" name="dias" class="campo">
                @foreach ([7, 15, 30, 60] as $opcion)
                    <option value="{{ $opcion }}" @selected($dias === $opcion)>{{ $opcion }} días</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primario">Generar reporte</button>
    </form>

    <p class="mb-4 text-sm text-gray-600">
        @if ($estado === 'vencidas')
            Dosis <strong>vencidas</strong> al {{ $hoy->format('d/m/Y') }}.
        @else
            Dosis que vencen entre el <strong>{{ $hoy->format('d/m/Y') }}</strong> y el <strong>{{ $limite->format('d/m/Y') }}</strong>.
        @endif
        {{ $filas->count() }} {{ $filas->count() === 1 ? 'registro' : 'registros' }}.
    </p>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Fecha</th>
                        <th class="px-5 py-3 font-semibold">Tipo</th>
                        <th class="px-5 py-3 font-semibold">Vacuna o producto</th>
                        <th class="px-5 py-3 font-semibold">Mascota</th>
                        <th class="px-5 py-3 font-semibold">Propietario</th>
                        <th class="px-5 py-3 font-semibold">Teléfono</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($filas as $fila)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3 {{ $fila['proxima']->lt($hoy) ? 'font-semibold text-red-600' : 'text-gray-800' }}">
                                {{ $fila['proxima']->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $fila['tipo'] }}</td>
                            <td class="px-5 py-3 text-gray-800">{{ $fila['nombre'] }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('expedientes.show', $fila['mascota_id']) }}" class="text-primario-300 hover:underline">
                                    {{ $fila['mascota'] }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $fila['cliente'] }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-800">{{ $fila['telefono'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                                {{ $estado === 'vencidas' ? 'No hay dosis vencidas.' : 'No hay dosis por vencer en ese período.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
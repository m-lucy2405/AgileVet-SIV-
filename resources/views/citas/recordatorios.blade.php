@extends('layouts.app')

@section('titulo', 'Recordatorios')

@section('contenido')
    @php
        // $recordatorios y $fecha los envía CitaController. Cada cita: id, hora, mascota, especie, cliente, telefono, motivo, veterinario
        $fecha = \Illuminate\Support\Carbon::parse($fecha ?? now()->addDay())->locale('es');
        $recordatorios = $recordatorios ?? [];
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-base font-semibold text-gray-800">Citas para recordar</h2>
                <p class="mt-0.5 text-sm text-gray-500 first-letter:uppercase">
                    {{ $fecha->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </p>
            </div>

            <span class="rounded-full bg-teal-50 px-3 py-1 text-sm font-medium text-teal-700">
                {{ count($recordatorios) }} {{ count($recordatorios) === 1 ? 'cita' : 'citas' }}
            </span>
        </div>

        @if (count($recordatorios))
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Hora</th>
                            <th class="px-5 py-3">Mascota</th>
                            <th class="px-5 py-3">Propietario</th>
                            <th class="px-5 py-3">Teléfono</th>
                            <th class="px-5 py-3">Motivo</th>
                            <th class="px-5 py-3">Veterinario</th>
                                                            @php
                                    $numero = preg_replace('/\D/', '', $cita['telefono']);
                                    $mensaje = "Hola {$cita['cliente']}, le recordamos la cita de {$cita['mascota']} el "
                                        . $fecha->isoFormat('dddd D [de] MMMM') . " a las {$cita['hora']} en Agro-Veterinaria La Mascota.";
                                @endphp
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="tel:{{ $numero }}" class="btn-secundario">Llamar</a>
                                        <a href="https://wa.me/503{{ $numero }}?text={{ rawurlencode($mensaje) }}"
                                            target="_blank" rel="noopener" class="btn-primario">WhatsApp</a>
                                    </div>
                                </td>
                            <th class="px-5 py-3 text-right">Contactar</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach ($recordatorios as $cita)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-5 py-3 font-medium text-gray-800">{{ $cita['hora'] }}</td>
                                <td class="px-5 py-3">
                                    <p class="text-gray-800">{{ $cita['mascota'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $cita['especie'] }}</p>
                                </td>
                                <td class="px-5 py-3 text-gray-800">{{ $cita['cliente'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-800">{{ $cita['telefono'] }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $cita['motivo'] }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $cita['veterinario'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-5 py-12 text-center">
                <p class="text-sm font-medium text-gray-700">No hay citas agendadas para este día.</p>
                <p class="mt-1 text-sm text-gray-500">Cuando se registren, aparecerán aquí con los datos de contacto del propietario.</p>
            </div>
        @endif
    </div>
@endsection
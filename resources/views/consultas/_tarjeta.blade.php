{{-- Tarjeta de una consulta. Necesita $mascota y $consulta (con veterinario y tratamientos cargados) --}}
<article class="mb-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="font-semibold text-gray-800">
            {{ $consulta->motivo }}
            @if ($consulta->fueCorregida())
                <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-gray-600">
                    Corregida a las {{ $consulta->updated_at->format('H:i') }}
                </span>
            @endif
        </h3>
        <span class="flex items-center gap-3 text-xs text-gray-500">
            {{ $consulta->fecha_consulta->format('d/m/Y H:i') }} · {{ $consulta->veterinario->nombre }}
            @if ($consulta->puedeSerCorregidaPor(auth()->user()))
                <a href="{{ route('consultas.edit', [$mascota, $consulta]) }}"
                    class="rounded-md px-2.5 py-1.5 font-medium text-primario-300 transition hover:bg-primario-500/10">
                    Corregir
                </a>
            @endif
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
        @if ($consulta->observaciones)
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-gray-500">Observaciones</dt>
                <dd class="whitespace-pre-line text-gray-800">{{ $consulta->observaciones }}</dd>
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
                            <td class="py-2 pr-3 text-gray-800">
                                {{ $tratamiento->medicamento }}
                                @if ($tratamiento->indicaciones)
                                    <span class="block text-xs text-gray-500">{{ $tratamiento->indicaciones }}</span>
                                @endif
                            </td>
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
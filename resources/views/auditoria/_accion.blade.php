{{-- Etiqueta de color de una acción. Necesita $auditoria --}}
@php
    $colores = [
        \App\Models\Auditoria::CREAR => 'bg-green-50 text-green-800',
        \App\Models\Auditoria::MODIFICAR => 'bg-amber-500/15 text-amber-300',
        \App\Models\Auditoria::ELIMINAR => 'bg-red-50 text-red-600',
    ];
@endphp

<span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colores[$auditoria->accion] ?? 'bg-gray-100 text-gray-600' }}">
    {{ $auditoria->nombreAccion() }}
</span>
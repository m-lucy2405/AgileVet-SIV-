@php
    $secciones = [
        'Principal' => [
            ['texto' => 'Inicio', 'url' => url('/dashboard'), 'ruta' => 'dashboard'],
            ['texto' => 'Citas', 'url' => '#', 'ruta' => 'citas*'],
        ],
        'Atención' => [
            ['texto' => 'Clientes y mascotas', 'url' => route('clientes.registro'), 'ruta' => 'clientes*'],
            ['texto' => 'Expedientes', 'url' => '#', 'ruta' => 'expedientes*'],
        ],
        'Negocio' => [
            ['texto' => 'Inventario', 'url' => '#', 'ruta' => 'inventario*'],
            ['texto' => 'Ventas', 'url' => '#', 'ruta' => 'ventas*'],
            ['texto' => 'Reportes', 'url' => '#', 'ruta' => 'reportes*'],
        ],
        'Administración' => [
            ['texto' => 'Usuarios', 'url' => route('usuarios.index'), 'ruta' => 'usuarios*'],
            ['texto' => 'Auditoría', 'url' => '#', 'ruta' => 'auditoria*'],
        ],
    ];
@endphp

<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-primario-900 text-primario-50 transition-transform duration-200 lg:translate-x-0">

    {{-- Logo y nombre --}}
    <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
        <a href="{{ url('/dashboard') }}" class="flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-acento-500 font-bold text-white">LM</span>
            <span class="leading-tight">
                <span class="block text-sm font-semibold">Agro-Veterinaria</span>
                <span class="block text-xs text-primario-200">La Mascota</span>
            </span>
        </a>

        <button id="btn-cerrar-menu" type="button" class="rounded-md p-1 hover:bg-white/10 lg:hidden" aria-label="Cerrar menú">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Menú --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        @foreach ($secciones as $titulo => $enlaces)
            <p class="mb-2 mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-primario-300 first:mt-0">
                {{ $titulo }}
            </p>

            @foreach ($enlaces as $enlace)
                @php $activo = request()->is($enlace['ruta']); @endphp
                <a href="{{ $enlace['url'] }}"
                    class="mb-1 block rounded-lg px-3 py-2 text-sm font-medium transition {{ $activo ? 'bg-primario-700 text-white' : 'text-primario-100 hover:bg-white/10' }}">
                    {{ $enlace['texto'] }}
                </a>
            @endforeach
        @endforeach
    </nav>
</aside>
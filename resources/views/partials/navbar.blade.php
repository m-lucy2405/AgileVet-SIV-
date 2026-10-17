@php
    $usuarioActual = auth()->user();
    $nombreUsuario = $usuarioActual?->nombre ?? 'Usuario';
    $nombreRol = $usuarioActual?->role?->nombre ?? 'Sin rol';
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white px-4 sm:px-6">
    {{-- Botón para abrir el menú en celular --}}
    <button id="btn-abrir-menu" type="button" class="rounded-md p-2 text-gray-600 hover:bg-gray-100 lg:hidden" aria-label="Abrir menú">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <h1 class="flex-1 truncate text-lg font-semibold text-gray-800">
        @yield('titulo', 'Inicio')
    </h1>

    {{-- Usuario --}}
    <div class="flex items-center gap-3">
        <div class="hidden text-right sm:block">
            <p class="text-sm font-medium text-gray-800">{{ $nombreUsuario }}</p>
            <p class="text-xs text-gray-500">{{ $nombreRol }}</p>
        </div>

        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primario-100 font-semibold text-primario-700">
            {{ strtoupper(mb_substr($nombreUsuario, 0, 1)) }}
        </span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100">
                Salir
            </button>
        </form>
    </div>
</header>
@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    @php
        $coloresRol = [
            'Administrador' => 'bg-acento-500/15 text-acento-600',
            'Veterinario' => 'bg-primario-100 text-primario-700',
            'Recepcionista' => 'bg-gray-100 text-gray-700',
        ];
    @endphp

    @if (session('status'))
        <div role="status" class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('usuario'))
        <div role="alert" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first('usuario') }}
        </div>
    @endif

    {{-- Barra de búsqueda y botón nuevo --}}
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" action="{{ route('usuarios.index') }}" class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" name="buscar" value="{{ request('buscar') }}"
                placeholder="Buscar por nombre o correo" class="campo sm:max-w-xs">

            <select name="rol" class="campo sm:w-48">
                <option value="">Todos los roles</option>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->id }}" @selected(request('rol') == $rol->id)>
                        {{ $rol->nombre }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-secundario">Filtrar</button>
        </form>

        <a href="{{ route('usuarios.create') }}" class="btn-primario">+ Nuevo usuario</a>
    </div>

    {{-- Tabla de usuarios --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Correo</th>
                        <th class="px-4 py-3 font-semibold">Rol</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($usuarios as $usuario)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primario-100 text-xs font-semibold text-primario-700">
                                        {{ mb_substr($usuario->nombre, 0, 1) }}
                                    </span>
                                    <span class="font-medium text-gray-800">{{ $usuario->nombre }}</span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-gray-600">{{ $usuario->email }}</td>

                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $coloresRol[$usuario->role->nombre] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $usuario->role->nombre }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                @if ($usuario->estado)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700">
                                        <span class="h-2 w-2 rounded-full bg-green-500"></span> Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500">
                                        <span class="h-2 w-2 rounded-full bg-gray-400"></span> Inactivo
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('usuarios.edit', $usuario) }}"
                                        class="rounded-md px-2.5 py-1.5 text-xs font-medium text-primario-700 hover:bg-primario-50">
                                        Editar
                                    </a>

                                    @if ($usuario->estado)
                                        <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-md px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                                Desactivar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-3 text-xs text-gray-500">
            Mostrando {{ $usuarios->firstItem() ?? 0 }}–{{ $usuarios->lastItem() ?? 0 }}
            de {{ $usuarios->total() }} usuarios
        </div>
    </div>

    <div class="mt-4">
        {{ $usuarios->links() }}
    </div>
@endsection
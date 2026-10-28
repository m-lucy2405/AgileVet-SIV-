@extends('layouts.app')

@section('titulo', 'Clientes y mascotas')

@section('contenido')
    <div class="mb-6 flex justify-end">
        <a href="{{ route('clientes.registro') }}" class="btn-primario">+ Nuevo cliente</a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @if ($clientes->count())
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Teléfono</th>
                            <th class="px-5 py-3">Correo</th>
                            <th class="px-5 py-3">Mascotas</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($clientes as $cliente)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-800">{{ $cliente->nombre }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-700">{{ $cliente->telefono }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $cliente->email ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $cliente->mascotas_count }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <a href="{{ route('clientes.show', $cliente) }}" class="btn-secundario">Ver ficha</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-5 py-3">
                {{ $clientes->links() }}
            </div>
        @else
            <p class="px-5 py-12 text-center text-sm text-gray-500">
                Todavía no hay clientes registrados.
            </p>
        @endif
    </div>
@endsection
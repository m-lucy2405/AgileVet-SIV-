@extends('layouts.app')

@section('titulo', 'Registro de cliente y mascota')

@section('contenido')
    @php
        // Catálogos de ejemplo para la maquetación.
        // Se reemplazan por las tablas especies y razas cuando exista el controlador.
        $especies = [1 => 'Canino', 2 => 'Felino', 3 => 'Ave', 4 => 'Bovino', 5 => 'Porcino'];

        $razasPorEspecie = [
            1 => [
                ['id' => 1, 'nombre' => 'Mestizo'],
                ['id' => 2, 'nombre' => 'Labrador'],
                ['id' => 3, 'nombre' => 'Pastor Alemán'],
                ['id' => 4, 'nombre' => 'Chihuahua'],
                ['id' => 5, 'nombre' => 'Poodle'],
            ],
            2 => [
                ['id' => 6, 'nombre' => 'Mestizo'],
                ['id' => 7, 'nombre' => 'Siamés'],
                ['id' => 8, 'nombre' => 'Persa'],
            ],
            3 => [
                ['id' => 9, 'nombre' => 'Gallina criolla'],
                ['id' => 10, 'nombre' => 'Perico australiano'],
            ],
            4 => [
                ['id' => 11, 'nombre' => 'Brahman'],
                ['id' => 12, 'nombre' => 'Holstein'],
            ],
            5 => [
                ['id' => 13, 'nombre' => 'Landrace'],
                ['id' => 14, 'nombre' => 'Duroc'],
            ],
        ];

        // Si hubo error de validación, vuelve a mostrar todas las mascotas que se habían agregado
        $indicesMascotas = array_keys(old('mascotas', [0 => []]));
    @endphp


     <div class="mx-auto max-w-5xl">
        <p class="mb-6 text-sm text-gray-600">
            Registra al propietario y a su mascota en un solo paso.
            Los campos con <span class="text-red-500">*</span> son obligatorios.
        </p>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

         <form id="form-registro" method="POST" action="#"
            data-razas='@json($razasPorEspecie)' class="space-y-6">
            @csrf

            {{-- Paso 1: propietario --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primario-700 text-sm font-semibold text-white">1</span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">Datos del propietario</h2>
                        <p class="text-xs text-gray-500">Persona responsable de la mascota.</p>
                    </div>|
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="cliente-nombre" class="etiqueta">Nombre completo <span class="text-red-500">*</span></label>
                        <input id="cliente-nombre" name="cliente[nombre]" type="text" required maxlength="150"
                            autofocus class="campo" value="{{ old('cliente.nombre') }}">
                        @error('cliente.nombre')
                            <p class="error-campo">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cliente-telefono" class="etiqueta">Teléfono <span class="text-red-500">*</span></label>
                        <input id="cliente-telefono" name="cliente[telefono]" type="tel" required maxlength="20"
                            inputmode="tel" placeholder="7000-0000" pattern="[0-9]{4}-?[0-9]{4}"
                            title="Formato: 7000-0000" class="campo" value="{{ old('cliente.telefono') }}">
                        @error('cliente.telefono')
                            <p class="error-campo">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cliente-email" class="etiqueta">Correo electrónico</label>
                        <input id="cliente-email" name="cliente[email]" type="email" maxlength="150"
                            placeholder="Opcional" class="campo" value="{{ old('cliente.email') }}">
                        @error('cliente.email')
                            <p class="error-campo">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="cliente-direccion" class="etiqueta">Dirección</label>
                        <input id="cliente-direccion" name="cliente[direccion]" type="text" maxlength="255"
                            placeholder="Colonia, calle, municipio" class="campo" value="{{ old('cliente.direccion') }}">
                    </div>
                </div>
            </section>

            {{-- Paso 2: mascotas --}}
            <section>
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primario-700 text-sm font-semibold text-white">2</span>
                        <div>
                            <h2 class="text-base font-semibold text-gray-800">Datos de la mascota</h2>
                            <p class="text-xs text-gray-500">Puedes registrar varias mascotas del mismo propietario.</p>
                        </div>
                    </div>

                    <button type="button" class="btn-secundario" data-agregar-mascota>+ Agregar otra mascota</button>
                </div>

                <div id="lista-mascotas" class="space-y-4" data-siguiente-indice="{{ max($indicesMascotas) + 1 }}">
                    @foreach ($indicesMascotas as $indice)
                        @include('clientes._mascota', ['indice' => $indice])
                    @endforeach
                </div>
            </section>

            {{-- Plantilla que el JavaScript copia al agregar otra mascota --}}
            <template id="plantilla-mascota">
                @include('clientes._mascota', ['indice' => '__INDICE__'])
            </template>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-end">
                <a href="{{ route('dashboard') }}" class="btn-secundario">Cancelar</a>
                <button type="submit" class="btn-primario">Guardar registro</button>
            </div>
        </form>
    </div>
@endsection
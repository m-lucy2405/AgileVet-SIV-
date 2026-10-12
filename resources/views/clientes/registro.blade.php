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
@endsection
<?php

namespace Database\Seeders;

use App\Models\CatalogoDesparasitante;
use App\Models\CatalogoVacuna;
use Illuminate\Database\Seeder;

/**
 * Vacunas y desparasitantes iniciales con sus intervalos habituales.
 * El administrador puede cambiarlos después desde el sistema.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class CatalogoSanitarioSeeder extends Seeder
{
    public function run(): void
    {
        // nombre => [especie (null = todas), intervalo en días]
        $vacunas = [
            'Antirrábica' => [null, 365],
            'Séxtuple canina (refuerzo de cachorro)' => ['Canino', 21],
            'Séxtuple canina (anual)' => ['Canino', 365],
            'Parvovirus (cachorro)' => ['Canino', 21],
            'Bordetella (tos de las perreras)' => ['Canino', 365],
            'Triple felina (refuerzo de gatito)' => ['Felino', 21],
            'Triple felina (anual)' => ['Felino', 365],
            'Leucemia felina' => ['Felino', 365],
        ];

        foreach ($vacunas as $nombre => [$especie, $dias]) {
            CatalogoVacuna::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['especie' => $especie, 'intervalo_dias' => $dias, 'activo' => true],
            );
        }

        // nombre => [especie, dosis sugerida, intervalo en días]
        $desparasitantes = [
            'Albendazol (interno)' => [null, '1 tableta por cada 10 kg', 90],
            'Praziquantel + Pirantel (interno)' => [null, '1 tableta por cada 10 kg', 90],
            'Fenbendazol (cachorros)' => [null, 'Según peso, 3 días seguidos', 15],
            'Pipeta antipulgas (externo)' => [null, '1 pipeta', 30],
            'Fluralaner (externo, 3 meses)' => ['Canino', '1 tableta según peso', 90],
        ];

        foreach ($desparasitantes as $nombre => [$especie, $dosis, $dias]) {
            CatalogoDesparasitante::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['especie' => $especie, 'dosis_sugerida' => $dosis, 'intervalo_dias' => $dias, 'activo' => true],
            );
        }
    }
}
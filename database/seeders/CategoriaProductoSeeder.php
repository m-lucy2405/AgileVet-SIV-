<?php

namespace Database\Seeders;

use App\Models\CategoriaProducto;
use Illuminate\Database\Seeder;

/**
 * Crea las categorías iniciales del inventario.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class CategoriaProductoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'Medicamentos' => 'Antibióticos, antiinflamatorios, analgésicos y otros tratamientos.',
            'Vacunas' => 'Vacunas para perros, gatos y animales de granja.',
            'Alimentos' => 'Concentrados, alimentos medicados y suplementos.',
            'Accesorios' => 'Collares, correas, comederos, juguetes y otros accesorios.',
            'Higiene' => 'Shampoos, antipulgas, desparasitantes externos y productos de limpieza.',
        ];

        foreach ($categorias as $nombre => $descripcion) {
            CategoriaProducto::query()->firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
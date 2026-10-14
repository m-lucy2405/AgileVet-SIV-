<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Usuario con acceso completo al sistema.',
            ],
            [
                'nombre' => 'Veterinario',
                'descripcion' => 'Usuario encargado de consultas y expedientes clínicos.',
            ],
            [
                'nombre' => 'Recepcionista',
                'descripcion' => 'Usuario encargado de citas y gestión de clientes.',
            ],
        ]);
    }
}

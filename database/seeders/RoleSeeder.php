<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
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
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['nombre' => $role['nombre']],
                ['descripcion' => $role['descripcion']],
            );
        }
    }
}

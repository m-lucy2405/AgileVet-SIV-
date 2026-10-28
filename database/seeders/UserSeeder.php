<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'Demo accounts are disabled in production. Use php artisan agilevet:create-admin instead.',
            );
        }

        $users = [
            [
                'nombre' => 'Administrador AgileVet',
                'email' => 'admin@agilevet.com',
                'rol' => 'Administrador',
            ],
            [
                'nombre' => 'Dr. Juan Veterinario',
                'email' => 'veterinario@agilevet.com',
                'rol' => 'Veterinario',
            ],
            [
                'nombre' => 'Recepcion AgileVet',
                'email' => 'recepcion@agilevet.com',
                'rol' => 'Recepcionista',
            ],
        ];

        foreach ($users as $user) {
            $roleId = Role::query()
                ->where('nombre', $user['rol'])
                ->value('id');

            if ($roleId === null) {
                throw new RuntimeException(
                    "No existe el rol {$user['rol']}. Ejecuta RoleSeeder primero.",
                );
            }

            User::query()->firstOrCreate(
                ['email' => $user['email']],
                [
                    'nombre' => $user['nombre'],
                    'password_hash' => 'password123',
                    'rol_id' => $roleId,
                    'estado' => true,
                ],
            );
        }
    }
}

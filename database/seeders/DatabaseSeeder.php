<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [RoleSeeder::class];

        // Los usuarios de prueba no se crean en el servidor de producción
        if (! app()->isProduction()) {
            $seeders[] = UserSeeder::class;
        }

        // Datos que el sistema necesita en todos los entornos
        $seeders[] = CategoriaProductoSeeder::class;
        $seeders[] = CatalogoSanitarioSeeder::class;

        $this->call($seeders);
    }
}
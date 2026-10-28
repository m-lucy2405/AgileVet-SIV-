<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [RoleSeeder::class];

        if (! app()->isProduction()) {
            $seeders[] = UserSeeder::class;
        }

        $seeders[] = CategoriaProductoSeeder::class;

        $this->call($seeders);
    }
}

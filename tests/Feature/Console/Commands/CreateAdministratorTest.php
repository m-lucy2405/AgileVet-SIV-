<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdministratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator_with_a_hidden_hashed_password(): void
    {
        $this->seed(RoleSeeder::class);

        $this->artisan('agilevet:create-admin')
            ->expectsQuestion('Nombre del administrador', 'AgileVet Administrator')
            ->expectsQuestion('Correo del administrador', 'admin@example.com')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'a-strong-password-123')
            ->expectsQuestion('Confirma la contraseña', 'a-strong-password-123')
            ->expectsOutput('Administrador creado correctamente.')
            ->assertExitCode(0);

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('AgileVet Administrator', $administrator->nombre);
        $this->assertTrue(Hash::check('a-strong-password-123', $administrator->password_hash));
    }

    public function test_it_rejects_a_short_password_without_creating_a_user(): void
    {
        $this->seed(RoleSeeder::class);

        $this->artisan('agilevet:create-admin')
            ->expectsQuestion('Nombre del administrador', 'AgileVet Administrator')
            ->expectsQuestion('Correo del administrador', 'admin@example.com')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'short')
            ->expectsOutput('La contraseña debe tener al menos 12 caracteres.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_rejects_a_password_confirmation_mismatch(): void
    {
        $this->seed(RoleSeeder::class);

        $this->artisan('agilevet:create-admin')
            ->expectsQuestion('Nombre del administrador', 'AgileVet Administrator')
            ->expectsQuestion('Correo del administrador', 'admin@example.com')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'a-strong-password-123')
            ->expectsQuestion('Confirma la contraseña', 'a-different-password-456')
            ->expectsOutput('Las contraseñas no coinciden.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
}

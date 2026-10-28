<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetAdministratorPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resets_the_password_for_an_existing_administrator(): void
    {
        $this->seed();

        $this->artisan('agilevet:reset-admin-password')
            ->expectsQuestion('Correo del administrador', 'admin@agilevet.com')
            ->expectsQuestion('Nueva contraseña (mínimo 12 caracteres)', 'a-new-strong-password-123')
            ->expectsQuestion('Confirma la nueva contraseña', 'a-new-strong-password-123')
            ->expectsOutput('Contraseña del administrador actualizada correctamente.')
            ->assertExitCode(0);

        $administrator = User::query()->where('email', 'admin@agilevet.com')->firstOrFail();

        $this->assertTrue(Hash::check('a-new-strong-password-123', $administrator->password_hash));
    }

    public function test_it_rejects_a_non_administrator_account(): void
    {
        $this->seed(RoleSeeder::class);
        $veterinarianRoleId = Role::query()->where('nombre', 'Veterinario')->value('id');
        $user = User::factory()->create(['rol_id' => $veterinarianRoleId]);

        $this->artisan('agilevet:reset-admin-password')
            ->expectsQuestion('Correo del administrador', $user->email)
            ->expectsOutput('No existe un administrador con ese correo.')
            ->assertExitCode(1);
    }

    public function test_it_rejects_a_password_confirmation_mismatch(): void
    {
        $this->seed();

        $this->artisan('agilevet:reset-admin-password')
            ->expectsQuestion('Correo del administrador', 'admin@agilevet.com')
            ->expectsQuestion('Nueva contraseña (mínimo 12 caracteres)', 'a-new-strong-password-123')
            ->expectsQuestion('Confirma la nueva contraseña', 'a-different-password-456')
            ->expectsOutput('Las contraseñas no coinciden.')
            ->assertExitCode(1);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@agilevet.com',
        ]);

        $administrator = User::query()->where('email', 'admin@agilevet.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $administrator->password_hash));
    }
}

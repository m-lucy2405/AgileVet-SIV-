<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnsureUserHasRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que solo un Administrador acceda a la gestión de usuarios.
     */
    public function test_administrator_can_access_user_management(): void
    {
        $role = Role::query()->create([
            'nombre' => 'Administrador',
            'descripcion' => 'Acceso completo al sistema.',
        ]);
        $user = User::factory()->create(['rol_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('usuarios.index'));

        $response->assertOk()
            ->assertSee(route('usuarios.index'));
    }

    /**
     * Verifica que los roles no administrativos reciban una respuesta 403.
     */
    #[DataProvider('rolesWithoutUserManagementAccess')]
    public function test_non_administrator_cannot_access_user_management(string $roleName): void
    {
        $role = Role::query()->create([
            'nombre' => $roleName,
            'descripcion' => 'Rol de prueba.',
        ]);
        $user = User::factory()->create(['rol_id' => $role->id]);

        $response = $this->actingAs($user)->get(route('usuarios.index'));

        $response->assertForbidden();
    }

    /**
     * Verifica que el encabezado muestre el nombre y rol reales del usuario.
     */
    public function test_dashboard_shows_the_authenticated_users_name_and_role(): void
    {
        $role = Role::query()->create([
            'nombre' => 'Veterinario',
            'descripcion' => 'Atiende pacientes.',
        ]);
        $user = User::factory()->create([
            'nombre' => 'Dra. Veterinaria',
            'rol_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Dra. Veterinaria')
            ->assertSee('Veterinario')
            ->assertDontSee(route('usuarios.index'));
    }

    /**
     * Proporciona los roles sin permiso para administrar usuarios.
     *
     * @return array<string, array{string}>
     */
    public static function rolesWithoutUserManagementAccess(): array
    {
        return [
            'Veterinario' => ['Veterinario'],
            'Recepcionista' => ['Recepcionista'],
        ];
    }

    /**
     * Verifica que la autenticación siga siendo obligatoria.
     */
    public function test_guest_is_redirected_to_login_before_role_check(): void
    {
        $response = $this->get(route('usuarios.index'));

        $response->assertRedirect(route('login'));
    }
}

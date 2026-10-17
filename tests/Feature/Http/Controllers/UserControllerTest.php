<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que el listado presente usuarios reales y escape contenido no confiable.
     *
     * @return void No retorna valor.
     */
    public function test_index_displays_real_users_and_escapes_names(): void
    {
        $administrator = $this->createUserWithRole('Administrador');
        $user = $this->createUserWithRole('Veterinario', [
            'nombre' => '<script>alert(1)</script>',
        ]);

        $response = $this->actingAs($administrator)->get(route('usuarios.index'));

        $response->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee($user->email);
    }

    /**
     * Verifica que los formularios de alta y edición muestren los roles asignables.
     *
     * @return void No retorna valor.
     */
    public function test_create_and_edit_forms_render_with_assignable_roles(): void
    {
        $administrator = $this->createUserWithRole('Administrador');
        $user = $this->createUserWithRole('Veterinario');
        $this->createRole('Recepcionista');

        $createResponse = $this->actingAs($administrator)->get(route('usuarios.create'));
        $editResponse = $this->actingAs($administrator)->get(route('usuarios.edit', $user));

        $createResponse->assertOk()
            ->assertSee(route('usuarios.store'), false)
            ->assertSee('Recepcionista');
        $editResponse->assertOk()
            ->assertSee(route('usuarios.update', $user), false)
            ->assertSee($user->nombre);
    }

    /**
     * Verifica que el alta persista el usuario y aplique hash a la contraseña.
     *
     * @return void No retorna valor.
     */
    public function test_store_creates_user_with_hashed_password(): void
    {
        $administrator = $this->createUserWithRole('Administrador');
        $role = $this->createRole('Veterinario');
        $password = 'SecurePass!2026';

        $response = $this->actingAs($administrator)->post(route('usuarios.store'), [
            'nombre' => 'Nueva Veterinaria',
            'email' => 'veterinaria.nueva@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'rol_id' => $role->id,
            'estado' => '1',
        ]);

        $response->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('status', 'El usuario se registró correctamente.');
        $this->assertDatabaseHas('users', [
            'nombre' => 'Nueva Veterinaria',
            'email' => 'veterinaria.nueva@example.com',
            'rol_id' => $role->id,
            'estado' => true,
        ]);

        $createdUser = User::query()
            ->where('email', 'veterinaria.nueva@example.com')
            ->firstOrFail();

        $this->assertNotSame($password, $createdUser->password_hash);
        $this->assertTrue(Hash::check($password, $createdUser->password_hash));
    }

    /**
     * Verifica que el alta rechace roles que no pertenecen a los perfiles del sistema.
     *
     * @return void No retorna valor.
     */
    public function test_store_rejects_unknown_role_without_creating_user(): void
    {
        $administrator = $this->createUserWithRole('Administrador');

        $response = $this->actingAs($administrator)->post(route('usuarios.store'), [
            'nombre' => 'Usuario con rol no válido',
            'email' => 'rol.invalido@example.com',
            'password' => 'SecurePass!2026',
            'password_confirmation' => 'SecurePass!2026',
            'rol_id' => 999,
        ]);

        $response->assertSessionHasErrors('rol_id');
        $this->assertDatabaseMissing('users', [
            'email' => 'rol.invalido@example.com',
        ]);
    }

    /**
     * Verifica que el alta rechace correos ya asignados a otra cuenta.
     *
     * @return void No retorna valor.
     */
    public function test_store_rejects_duplicate_email(): void
    {
        $administrator = $this->createUserWithRole('Administrador');
        $existingUser = $this->createUserWithRole('Veterinario', [
            'email' => 'existing.user@example.com',
        ]);
        $role = $this->createRole('Recepcionista');

        $response = $this->actingAs($administrator)->post(route('usuarios.store'), [
            'nombre' => 'Correo duplicado',
            'email' => $existingUser->email,
            'password' => 'SecurePass!2026',
            'password_confirmation' => 'SecurePass!2026',
            'rol_id' => $role->id,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 2);
    }

    /**
     * Verifica que el alta rechace contraseñas menores a la longitud mínima.
     *
     * @return void No retorna valor.
     */
    public function test_store_rejects_short_password(): void
    {
        $administrator = $this->createUserWithRole('Administrador');
        $role = $this->createRole('Veterinario');

        $response = $this->actingAs($administrator)->post(route('usuarios.store'), [
            'nombre' => 'Contraseña débil',
            'email' => 'weak.password@example.com',
            'password' => 'Short1!',
            'password_confirmation' => 'Short1!',
            'rol_id' => $role->id,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', [
            'email' => 'weak.password@example.com',
        ]);
    }

    /**
     * Verifica que la edición permita conservar la contraseña y desactivar la cuenta.
     *
     * @return void No retorna valor.
     */
    public function test_update_preserves_password_when_blank_and_updates_user_data(): void
    {
        $administrator = $this->createUserWithRole('Administrador');
        $user = $this->createUserWithRole('Veterinario');
        $receptionistRole = $this->createRole('Recepcionista');
        $originalPasswordHash = $user->password_hash;

        $response = $this->actingAs($administrator)->put(route('usuarios.update', $user), [
            'nombre' => 'Veterinario Actualizado',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'rol_id' => $receptionistRole->id,
            'estado' => '0',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nombre' => 'Veterinario Actualizado',
            'email' => $user->email,
            'rol_id' => $receptionistRole->id,
            'estado' => false,
        ]);
        $this->assertSame($originalPasswordHash, $user->fresh()->password_hash);
    }

    /**
     * Verifica que la acción de desactivar conserve el registro para el historial.
     *
     * @return void No retorna valor.
     */
    public function test_destroy_deactivates_user_without_deleting_record(): void
    {
        $administrator = $this->createUserWithRole('Administrador');
        $user = $this->createUserWithRole('Recepcionista');

        $response = $this->actingAs($administrator)->delete(route('usuarios.destroy', $user));

        $response->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('status', 'El usuario se desactivó correctamente.');
        $this->assertModelExists($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'estado' => false,
        ]);
    }

    /**
     * Verifica que un administrador no pueda desactivar su propia cuenta.
     *
     * @return void No retorna valor.
     */
    public function test_destroy_rejects_deactivation_of_authenticated_user(): void
    {
        $administrator = $this->createUserWithRole('Administrador');

        $response = $this->actingAs($administrator)
            ->delete(route('usuarios.destroy', $administrator));

        $response->assertSessionHasErrors('usuario');
        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
            'estado' => true,
        ]);
    }

    /**
     * Verifica que los roles no administrativos no puedan crear usuarios.
     *
     * @return void No retorna valor.
     */
    public function test_non_administrator_cannot_create_user(): void
    {
        $veterinarian = $this->createUserWithRole('Veterinario');

        $response = $this->actingAs($veterinarian)->post(route('usuarios.store'), []);

        $response->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Crea un usuario para un rol definido en el catálogo de roles.
     *
     * @param  string  $roleName  Nombre del rol a crear.
     * @param  array<string, mixed>  $attributes  Atributos adicionales del usuario.
     * @return User Usuario persistido para la prueba.
     */
    private function createUserWithRole(string $roleName, array $attributes = []): User
    {
        $role = $this->createRole($roleName);

        return User::factory()->create([
            ...$attributes,
            'rol_id' => $role->id,
        ]);
    }

    /**
     * Crea un rol de prueba sin marcas de tiempo.
     *
     * @param  string  $name  Nombre del rol.
     * @return Role Rol persistido para la prueba.
     */
    private function createRole(string $name): Role
    {
        return Role::query()->create([
            'nombre' => $name,
            'descripcion' => "Rol de prueba: {$name}",
        ]);
    }
}

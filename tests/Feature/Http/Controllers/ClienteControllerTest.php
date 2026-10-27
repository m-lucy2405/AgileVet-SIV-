<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Cliente;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica el registro HTTP transaccional de clientes y mascotas.
 *
 * @package Tests\Feature\Http\Controllers
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class ClienteControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Redirige a autenticación y no persiste datos para visitantes.
     *
     * @package Tests\Feature\Http\Controllers
     * @return void No retorna un valor.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_guest_is_redirected_to_login_without_creating_records(): void
    {
        $response = $this->post(route('clientes.store'), $this->registrationPayload());

        $response->assertRedirectToRoute('login');
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('mascotas', 0);
    }

    /**
     * Muestra el formulario conectado al endpoint de registro.
     *
     * @package Tests\Feature\Http\Controllers
     * @return void No retorna un valor.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_registration_form_posts_to_client_store_route(): void
    {
        $user = $this->createAuthenticatedUser();

        $response = $this->actingAs($user)->get(route('clientes.registro'));

        $response->assertOk()
            ->assertSee('action="'.route('clientes.store').'"', false);
    }

    /**
     * Persiste el cliente y todas sus mascotas relacionadas en una sola operación.
     *
     * @package Tests\Feature\Http\Controllers
     * @return void No retorna un valor.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_authenticated_user_can_register_a_client_with_multiple_pets(): void
    {
        $user = $this->createAuthenticatedUser();
        $payload = $this->registrationPayload();

        $response = $this->actingAs($user)
            ->post(route('clientes.store'), $payload);

        $response->assertRedirectToRoute('clientes.registro')
            ->assertSessionHas(
                'success',
                'El cliente y sus mascotas se registraron correctamente.',
            );

        $this->assertDatabaseHas('clientes', [
            'nombre' => 'María López',
            'telefono' => '7000-1234',
            'direccion' => 'San Salvador',
            'email' => 'maria@example.com',
        ]);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('mascotas', 2);

        $cliente = Cliente::query()
            ->where('email', 'maria@example.com')
            ->firstOrFail();

        $this->assertSame(
            ['Luna', 'Michi'],
            $cliente->mascotas()->orderBy('nombre')->pluck('nombre')->all(),
        );
        $this->assertDatabaseHas('mascotas', [
            'cliente_id' => $cliente->id,
            'nombre' => 'Luna',
            'esterilizado' => true,
        ]);
    }

    /**
     * Rechaza especies no admitidas sin persistir parcialmente el registro.
     *
     * @package Tests\Feature\Http\Controllers
     * @return void No retorna un valor.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_invalid_pet_data_does_not_create_a_client_or_pets(): void
    {
        $user = $this->createAuthenticatedUser();
        $payload = $this->registrationPayload();
        $payload['mascotas'][0]['especie'] = 'Reptil no admitido';

        $response = $this->actingAs($user)
            ->post(route('clientes.store'), $payload);

        $response->assertInvalid(['mascotas.0.especie']);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('mascotas', 0);
    }

    /**
     * Rechaza atributos inesperados para impedir asignaciones no autorizadas.
     *
     * @package Tests\Feature\Http\Controllers
     * @return void No retorna un valor.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function test_unexpected_client_attributes_are_rejected(): void
    {
        $user = $this->createAuthenticatedUser();
        $payload = $this->registrationPayload();
        $payload['cliente']['id'] = 42;

        $response = $this->actingAs($user)
            ->post(route('clientes.store'), $payload);

        $response->assertInvalid(['cliente']);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('mascotas', 0);
    }

    /**
     * Devuelve los datos válidos de un cliente con dos mascotas de prueba.
     *
     * @package Tests\Feature\Http\Controllers
     * @return array<string, mixed> Datos para la petición de registro.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function registrationPayload(): array
    {
        return [
            'cliente' => [
                'nombre' => 'María López',
                'telefono' => '7000-1234',
                'direccion' => 'San Salvador',
                'email' => 'maria@example.com',
            ],
            'mascotas' => [
                [
                    'nombre' => 'Luna',
                    'especie' => 'Canino',
                    'raza' => 'Labrador',
                    'sexo' => 'hembra',
                    'fecha_nacimiento' => '2022-05-10',
                    'color' => 'Dorado',
                    'esterilizado' => '1',
                ],
                [
                    'nombre' => 'Michi',
                    'especie' => 'Felino',
                    'raza' => 'Siamés',
                    'sexo' => 'macho',
                    'fecha_nacimiento' => '2023-02-15',
                    'color' => 'Crema',
                    'esterilizado' => '0',
                ],
            ],
        ];
    }

    /**
     * Crea un usuario autenticado con un rol válido para la llave foránea.
     *
     * @package Tests\Feature\Http\Controllers
     * @return User Usuario persistido para la prueba.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    private function createAuthenticatedUser(): User
    {
        $role = Role::query()->create([
            'nombre' => 'Recepcionista',
            'descripcion' => 'Rol de prueba',
        ]);

        return User::factory()->create([
            'rol_id' => $role->id,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Consulta;
use App\Models\Expediente;
use App\Models\Mascota;
use App\Models\Role;
use App\Models\Tratamiento;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del módulo de auditoría: registro automático de cambios (trait Auditable),
 * registro de sesiones y pantalla del administrador.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00:00'));
    }

    /* ------------------------------------------------------------------
     | Trait Auditable
     * ------------------------------------------------------------------ */

    public function test_creating_a_model_registers_its_values(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $mascota = $this->createMascota();

        $this->actingAs($veterinario);
        $expediente = $mascota->expediente()->create(['fecha_apertura' => now(), 'alergias' => 'Polen']);

        $registro = $this->ultimoRegistro('expedientes');

        $this->assertSame(Auditoria::CREAR, $registro->accion);
        $this->assertSame($veterinario->id, $registro->usuario_id);
        $this->assertSame($expediente->id, $registro->registro_id);
        $this->assertNull($registro->valores_anteriores);
        $this->assertSame('Polen', $registro->valores_nuevos['alergias']);
    }

    public function test_updating_a_model_registers_only_changed_fields_with_before_and_after(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $expediente = $this->createExpediente(['alergias' => 'Ninguna', 'antecedentes' => 'Sin antecedentes']);

        $this->actingAs($veterinario);
        $expediente->update(['alergias' => 'Penicilina', 'antecedentes' => 'Sin antecedentes']);

        $registro = $this->ultimoRegistro('expedientes');

        $this->assertSame(Auditoria::MODIFICAR, $registro->accion);
        // Solo las alergias cambiaron: los antecedentes no deben aparecer
        $this->assertSame(['alergias' => 'Ninguna'], $registro->valores_anteriores);
        $this->assertSame(['alergias' => 'Penicilina'], $registro->valores_nuevos);
    }

    public function test_touching_only_timestamps_does_not_register_anything(): void
    {
        $expediente = $this->createExpediente();

        $this->actingAs($this->createUser('Veterinario'));
        $this->travel(1)->hours();
        $expediente->touch();

        $this->assertSame(0, Auditoria::query()->where('tabla_afectada', 'expedientes')->count());
    }

    public function test_deleting_a_model_registers_its_previous_values(): void
    {
        $veterinario = $this->createUser('Veterinario');
        $expediente = $this->createExpediente();
        $consulta = Consulta::query()->create([
            'expediente_id' => $expediente->id,
            'veterinario_id' => $veterinario->id,
            'fecha_consulta' => now(),
            'motivo' => 'Control',
            'diagnostico' => 'Sano',
        ]);
        $tratamiento = Tratamiento::query()->create([
            'consulta_id' => $consulta->id,
            'medicamento' => 'Amoxicilina',
            'dosis' => '1 tableta',
            'frecuencia' => 'cada 8 horas',
            'duracion' => '7 días',
        ]);

        $this->actingAs($veterinario);
        $tratamiento->delete();

        $registro = $this->ultimoRegistro('tratamientos');

        $this->assertSame(Auditoria::ELIMINAR, $registro->accion);
        $this->assertSame('Amoxicilina', $registro->valores_anteriores['medicamento']);
        $this->assertNull($registro->valores_nuevos);
    }

    public function test_passwords_are_never_stored(): void
    {
        $administrador = $this->createUser('Administrador');
        $usuario = $this->createUser('Recepcionista');

        $this->actingAs($administrador);

        // Solo la contraseña: no hay cambios que mostrar, así que no se registra nada
        $usuario->update(['password_hash' => 'otra-contraseña-segura']);
        $this->assertSame(0, Auditoria::query()->where('tabla_afectada', 'users')->where('accion', Auditoria::MODIFICAR)->count());

        // Nombre y contraseña: se registra el nombre, pero nunca la contraseña
        $usuario->update(['nombre' => 'Nuevo nombre', 'password_hash' => 'otra-contraseña-mas']);
        $registro = $this->ultimoRegistro('users');

        $this->assertSame(['nombre' => 'Nuevo nombre'], $registro->valores_nuevos);
        $this->assertArrayNotHasKey('password_hash', $registro->valores_anteriores);

        // Ningún registro de la auditoría contiene la contraseña
        Auditoria::query()->get()->each(function (Auditoria $auditoria): void {
            $this->assertArrayNotHasKey('password_hash', $auditoria->valores_nuevos ?? []);
            $this->assertArrayNotHasKey('password_hash', $auditoria->valores_anteriores ?? []);
        });
    }

    public function test_nothing_is_registered_without_an_authenticated_user(): void
    {
        // Sin actingAs(): como en los seeders o en la consola
        $this->createExpediente(['alergias' => 'Polen']);

        $this->assertSame(0, Auditoria::query()->count());
    }

    /* ------------------------------------------------------------------
     | Sesiones
     * ------------------------------------------------------------------ */

    public function test_login_and_logout_are_registered(): void
    {
        $usuario = $this->createUser('Recepcionista');

        event(new Login('web', $usuario, false));
        event(new Logout('web', $usuario));

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $usuario->id,
            'accion' => Auditoria::INICIO_SESION,
            'tabla_afectada' => 'users',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $usuario->id,
            'accion' => Auditoria::CIERRE_SESION,
            'tabla_afectada' => 'users',
        ]);
    }

    /* ------------------------------------------------------------------
     | Pantalla del administrador
     * ------------------------------------------------------------------ */

    public function test_administrator_sees_the_list_and_filters_by_action(): void
    {
        $administrador = $this->createUser('Administrador');
        $veterinario = $this->createUser('Veterinario');
        event(new Login('web', $veterinario, false));

        $this->actingAs($veterinario);
        $this->createExpediente(['alergias' => 'Polen']);

        // Sin filtros: aparecen el inicio de sesión y la creación del expediente
        $this->actingAs($administrador)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertViewHas('registros', function ($registros): bool {
                $acciones = collect($registros->items())->pluck('accion');

                return $registros->total() === 2
                    && $acciones->contains(Auditoria::INICIO_SESION)
                    && $acciones->contains(Auditoria::CREAR);
            });

        // Filtrando por inicio de sesión: solo queda ese registro
        $this->get(route('auditoria.index', ['accion' => Auditoria::INICIO_SESION]))
            ->assertOk()
            ->assertViewHas('registros', function ($registros): bool {
                return $registros->total() === 1
                    && $registros->first()->accion === Auditoria::INICIO_SESION;
            });
    }

    public function test_administrator_sees_the_detail_with_before_and_after(): void
    {
        $administrador = $this->createUser('Administrador');
        $expediente = $this->createExpediente(['alergias' => 'Ninguna']);

        $this->actingAs($this->createUser('Veterinario'));
        $expediente->update(['alergias' => 'Penicilina']);
        $registro = $this->ultimoRegistro('expedientes');

        $this->actingAs($administrador)
            ->get(route('auditoria.show', $registro))
            ->assertOk()
            ->assertSee('Modificación')
            ->assertSee('Alergias')
            ->assertSee('Ninguna')
            ->assertSee('Penicilina');
    }

    public function test_receptionist_cannot_access_the_audit(): void
    {
        $recepcionista = $this->createUser('Recepcionista');
        $registro = Auditoria::query()->create([
            'usuario_id' => $recepcionista->id,
            'accion' => Auditoria::INICIO_SESION,
            'tabla_afectada' => 'users',
            'registro_id' => $recepcionista->id,
            'fecha_accion' => now(),
        ]);

        $this->actingAs($recepcionista);

        $this->get(route('auditoria.index'))->assertForbidden();
        $this->get(route('auditoria.show', $registro))->assertForbidden();
    }

    public function test_audit_requires_authentication(): void
    {
        $this->get(route('auditoria.index'))->assertRedirect(route('login'));
    }

    public function test_date_filter_rejects_an_inverted_range(): void
    {
        $this->actingAs($this->createUser('Administrador'))
            ->get(route('auditoria.index', ['desde' => '2026-10-20', 'hasta' => '2026-10-10']))
            ->assertSessionHasErrors(['hasta' => 'La fecha final debe ser igual o posterior a la fecha inicial.']);
    }

    /* ------------------------------------------------------------------
     | Ayudantes
     * ------------------------------------------------------------------ */

    /**
     * Crea un usuario activo con el rol indicado (sin sesión, no queda en la auditoría).
     */
    private function createUser(string $rol): User
    {
        $role = Role::query()->firstOrCreate(
            ['nombre' => $rol],
            ['descripcion' => "Rol {$rol} para pruebas."],
        );

        return User::factory()->create(['rol_id' => $role->id]);
    }

    /**
     * Crea una mascota con su propietario.
     */
    private function createMascota(): Mascota
    {
        $cliente = Cliente::query()->create([
            'nombre' => 'Cliente de prueba',
            'telefono' => '70000000',
            'direccion' => 'San Miguel',
        ]);

        return Mascota::query()->create([
            'cliente_id' => $cliente->id,
            'nombre' => 'Firulais',
            'especie' => 'Canino',
            'sexo' => 'macho',
            'esterilizado' => false,
        ]);
    }

    /**
     * Crea un expediente con los datos indicados.
     *
     * @param  array<string, mixed>  $datos  Datos adicionales del expediente.
     */
    private function createExpediente(array $datos = []): Expediente
    {
        return $this->createMascota()->expediente()->create(['fecha_apertura' => now(), ...$datos]);
    }

    /**
     * Devuelve el registro de auditoría más reciente de una tabla.
     */
    private function ultimoRegistro(string $tabla): Auditoria
    {
        return Auditoria::query()->where('tabla_afectada', $tabla)->latest('id')->firstOrFail();
    }
}
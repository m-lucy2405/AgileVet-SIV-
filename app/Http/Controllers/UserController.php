<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Gestiona las cuentas de usuario del sistema.
 */
class UserController extends Controller
{
    /**
     * Roles que pueden asignarse a una cuenta del sistema.
     *
     * @var array<int, string>
     */
    private const ROLE_NAMES = [
        'Administrador',
        'Veterinario',
        'Recepcionista',
    ];

    /**
     * Muestra y filtra el listado paginado de usuarios.
     *
     * @param  Request  $request  Petición con filtros opcionales de nombre, correo y rol.
     * @return View Vista del listado de usuarios.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'rol' => ['nullable', 'integer', $this->roleIdRule()],
        ]);

        $usersQuery = User::query()
            ->with('role')
            ->orderBy('nombre')
            ->orderBy('id');

        if (filled($filters['buscar'] ?? null)) {
            $search = $filters['buscar'];

            $usersQuery->where(function (EloquentBuilder $query) use ($search): void {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['rol'])) {
            $usersQuery->where('rol_id', $filters['rol']);
        }

        return view('usuarios.index', [
            'usuarios' => $usersQuery->paginate(10)->withQueryString(),
            'roles' => $this->availableRoles(),
        ]);
    }

    /**
     * Muestra el formulario para registrar un usuario.
     *
     * @return View Vista de creación y roles asignables.
     */
    public function create(): View
    {
        return view('usuarios.create', [
            'roles' => $this->availableRoles(),
        ]);
    }

    /**
     * Valida y registra un usuario dentro de una transacción.
     *
     * @param  Request  $request  Petición con los datos del nuevo usuario.
     * @return RedirectResponse Redirección al listado con mensaje de éxito.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(12)->max(128), 'confirmed'],
            'rol_id' => ['required', 'integer', $this->roleIdRule()],
            'estado' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($validated): void {
            User::query()->create([
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                'password_hash' => $validated['password'],
                'rol_id' => $validated['rol_id'],
                'estado' => $validated['estado'] ?? true,
            ]);
        });

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'El usuario se registró correctamente.');
    }

    /**
     * Muestra el formulario con los datos del usuario seleccionado.
     *
     * @param  User  $user  Usuario resuelto por route model binding.
     * @return View Vista de edición y roles asignables.
     */
    public function edit(User $user): View
    {
        return view('usuarios.edit', [
            'usuario' => $user,
            'roles' => $this->availableRoles(),
        ]);
    }

    /**
     * Valida y guarda los cambios del usuario dentro de una transacción.
     *
     * @param  Request  $request  Petición con los datos modificados.
     * @param  User  $user  Usuario resuelto por route model binding.
     * @return RedirectResponse Redirección al listado con mensaje de éxito.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', Password::min(12)->max(128), 'confirmed'],
            'rol_id' => ['required', 'integer', $this->roleIdRule()],
            'estado' => ['required', 'boolean'],
        ]);

        if (! $validated['estado'] && $request->user()?->is($user)) {
            return back()
                ->withErrors(['estado' => 'No puedes desactivar tu propia cuenta.']);
        }

        DB::transaction(function () use ($validated, $user): void {
            $attributes = [
                'nombre' => $validated['nombre'],
                'email' => $validated['email'],
                'rol_id' => $validated['rol_id'],
                'estado' => $validated['estado'],
            ];

            if (filled($validated['password'] ?? null)) {
                $attributes['password_hash'] = $validated['password'];
            }

            $user->update($attributes);
        });

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'Los datos del usuario se actualizaron correctamente.');
    }

    /**
     * Desactiva una cuenta sin eliminar su historial del sistema.
     *
     * @param  Request  $request  Petición HTTP actual.
     * @param  User  $user  Usuario resuelto por route model binding.
     * @return RedirectResponse Redirección al listado con resultado de la operación.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()
                ->withErrors(['usuario' => 'No puedes desactivar tu propia cuenta.']);
        }

        DB::transaction(function () use ($user): void {
            $user->update(['estado' => false]);
        });

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'El usuario se desactivó correctamente.');
    }

    /**
     * Obtiene únicamente los roles admitidos para usuarios del sistema.
     *
     * @return Collection<int, Role> Roles asignables.
     */
    private function availableRoles(): Collection
    {
        return Role::query()
            ->whereIn('nombre', self::ROLE_NAMES)
            ->orderBy('id')
            ->get();
    }

    /**
     * Crea una regla que limita el rol a los tres perfiles del sistema.
     *
     * @return Exists Regla de existencia restringida a roles permitidos.
     */
    private function roleIdRule(): Exists
    {
        return Rule::exists('roles', 'id')
            ->where(fn (QueryBuilder $query): QueryBuilder => $query->whereIn('nombre', self::ROLE_NAMES));
    }
}

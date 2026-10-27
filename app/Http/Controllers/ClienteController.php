<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Registra, lista y edita los clientes y las mascotas que tienen a su cargo.
 *
 * @package App\Http\Controllers
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class ClienteController extends Controller
{
    /**
     * Lista los clientes registrados con la cantidad de mascotas de cada uno.
     */
    public function index(): View
    {
        $clientes = Cliente::query()
            ->withCount('mascotas')
            ->orderBy('nombre')
            ->paginate(15);

        return view('clientes.index', ['clientes' => $clientes]);
    }

    /**
     * Valida y registra un cliente junto con sus mascotas en una transacción.
     *
     * @package App\Http\Controllers
     * @param  StoreClienteRequest  $request  Datos validados del cliente y sus mascotas.
     * @return RedirectResponse Redirección al formulario con un mensaje de resultado.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function store(StoreClienteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $cliente = Cliente::query()->create($validated['cliente']);

            $mascotas = array_map(
                static fn (array $mascota): array => [
                    ...$mascota,
                    'esterilizado' => $mascota['esterilizado'] ?? false,
                ],
                $validated['mascotas'],
            );

            $cliente->mascotas()->createMany($mascotas);
        });

        return redirect()
            ->route('clientes.registro')
            ->with('success', 'El cliente y sus mascotas se registraron correctamente.');
    }

    /**
     * Muestra la ficha del cliente con sus mascotas.
     */
    public function show(Cliente $cliente): View
    {
        $cliente->load(['mascotas' => fn ($query) => $query->orderBy('nombre')]);

        return view('clientes.show', ['cliente' => $cliente]);
    }

    /**
     * Muestra el formulario para editar los datos del cliente.
     */
    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', ['cliente' => $cliente]);
    }

    /**
     * Actualiza los datos del cliente.
     */
    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'direccion' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:150'],
        ]);

        $cliente->update($datos);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('success', 'Los datos del cliente se actualizaron correctamente.');
    }
}
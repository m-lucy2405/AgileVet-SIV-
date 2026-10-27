<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Registra clientes y las mascotas que tienen a su cargo.
 *
 * @package App\Http\Controllers
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class ClienteController extends Controller
{
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
}

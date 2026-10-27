<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el registro conjunto de un cliente y sus mascotas.
 *
 * @package App\Http\Requests
 * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
 */
class StoreClienteRequest extends FormRequest
{
    /**
     * Permite el registro a usuarios autenticados por la ruta web.
     *
     * @package App\Http\Requests
     * @return bool Indica si la petición puede continuar a la validación.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Define las reglas para los datos del cliente y sus mascotas.
     *
     * @package App\Http\Requests
     * @return array<string, array<mixed>> Reglas de validación por campo.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function rules(): array
    {
        return [
            'cliente' => ['required', 'array:nombre,telefono,direccion,email'],
            'cliente.nombre' => ['required', 'string', 'max:150'],
            'cliente.telefono' => ['required', 'string', 'max:20'],
            'cliente.direccion' => ['required', 'string', 'max:255'],
            'cliente.email' => ['nullable', 'string', 'email:rfc', 'max:150'],
            'mascotas' => ['required', 'array', 'min:1'],
            'mascotas.*' => [
                'required',
                'array:nombre,especie,raza,sexo,fecha_nacimiento,color,esterilizado',
            ],
            'mascotas.*.nombre' => ['required', 'string', 'max:100'],
            'mascotas.*.especie' => [
                'required',
                'string',
                Rule::in(['Canino', 'Felino', 'Ave', 'Bovino', 'Porcino']),
            ],
            'mascotas.*.raza' => ['nullable', 'string', 'max:100'],
            'mascotas.*.sexo' => ['required', 'string', Rule::in(['macho', 'hembra'])],
            'mascotas.*.fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'mascotas.*.color' => ['nullable', 'string', 'max:50'],
            'mascotas.*.esterilizado' => ['sometimes', 'boolean'],
        ];
    }
}

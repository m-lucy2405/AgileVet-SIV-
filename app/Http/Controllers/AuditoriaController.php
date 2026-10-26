<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Muestra al administrador el registro de acciones del sistema:
 * quién hizo qué, dónde, cuándo y qué valores cambió.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class AuditoriaController extends Controller
{
    /**
     * Registros por página en la lista.
     */
    private const POR_PAGINA = 20;

    /**
     * Lista los registros de auditoría con filtros opcionales.
     *
     * @param  Request  $request  Petición con los filtros de usuario, acción, módulo y fechas.
     * @return View Vista con la lista paginada.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'usuario_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'accion' => ['nullable', 'string', Rule::in(array_keys(Auditoria::NOMBRES_ACCIONES))],
            'tabla' => ['nullable', 'string', Rule::in(array_keys(Auditoria::NOMBRES_TABLAS))],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);

        $registros = Auditoria::query()
            ->with('usuario:id,nombre')
            ->when($filtros['usuario_id'] ?? null, fn ($query, $id) => $query->where('usuario_id', $id))
            ->when($filtros['accion'] ?? null, fn ($query, $accion) => $query->where('accion', $accion))
            ->when($filtros['tabla'] ?? null, fn ($query, $tabla) => $query->where('tabla_afectada', $tabla))
            ->when($filtros['desde'] ?? null, fn ($query, $desde) => $query->whereDate('fecha_accion', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($query, $hasta) => $query->whereDate('fecha_accion', '<=', $hasta))
            ->orderByDesc('fecha_accion')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('auditoria.index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'usuarios' => User::query()->orderBy('nombre')->get(['id', 'nombre']),
            'acciones' => Auditoria::NOMBRES_ACCIONES,
            'tablas' => Auditoria::NOMBRES_TABLAS,
        ]);
    }

    /**
     * Muestra el detalle de un registro: cada campo con su valor antes y después.
     *
     * @param  Auditoria  $auditoria  Registro resuelto mediante route model binding.
     * @return View Vista con el detalle del cambio.
     *
     * @author Equipo de desarrollo AgileVet-SIV
     */
    public function show(Auditoria $auditoria): View
    {
        $auditoria->load('usuario:id,nombre,email');

        $anteriores = $auditoria->valores_anteriores ?? [];
        $nuevos = $auditoria->valores_nuevos ?? [];

        // Todos los campos que aparecen en el cambio, sin repetir
        $campos = array_values(array_unique(array_merge(array_keys($anteriores), array_keys($nuevos))));

        return view('auditoria.show', [
            'auditoria' => $auditoria,
            'campos' => $campos,
            'anteriores' => $anteriores,
            'nuevos' => $nuevos,
        ]);
    }
}
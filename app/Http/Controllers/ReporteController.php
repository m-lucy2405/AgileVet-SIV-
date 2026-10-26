<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Desparasitacion;
use App\Models\User;
use App\Models\Vacunacion;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reportes de la clínica: citas, consultas por veterinario, diagnósticos
 * frecuentes y próximas dosis de vacunas y desparasitaciones.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class ReporteController extends Controller
{
    /**
     * Roles que pueden ver los reportes de gestión (citas, consultas y diagnósticos).
     *
     * @var array<int, string>
     */
    private const ROLES_GESTION = ['Administrador', 'Veterinario'];

    /**
     * Roles que pueden ver el reporte de próximas dosis.
     *
     * @var array<int, string>
     */
    private const ROLES_DOSIS = ['Administrador', 'Veterinario', 'Recepcionista'];

    /**
     * Estados de cita y su nombre legible.
     *
     * @var array<string, string>
     */
    private const ESTADOS_CITA = [
        'pendiente' => 'Pendiente',
        'atendida' => 'Atendida',
        'cancelada' => 'Cancelada',
        'no_asistio' => 'No asistió',
    ];

    /**
     * Página principal con la lista de reportes disponibles para el usuario.
     *
     * @param  Request  $request  Petición del usuario autenticado.
     * @return View Vista con los reportes disponibles.
     */
    public function index(Request $request): View
    {
        return view('reportes.index', [
            'puedeVerGestion' => $this->tieneRol($request, self::ROLES_GESTION),
            'puedeVerDosis' => $this->tieneRol($request, self::ROLES_DOSIS),
        ]);
    }

    /**
     * Reporte de citas por estado y por veterinario en un rango de fechas.
     *
     * @param  Request  $request  Petición con el rango de fechas opcional.
     * @return View Vista con los totales de citas.
     */
    public function citas(Request $request): View
    {
        $this->permitir($request, self::ROLES_GESTION);
        [$desde, $hasta] = $this->rangoFechas($request);

        $filas = Cita::query()
            ->whereBetween('fecha_cita', [$desde, $hasta])
            ->selectRaw('veterinario_id, estado, COUNT(*) as total')
            ->groupBy('veterinario_id', 'estado')
            ->get();

        $contar = fn (Collection $filas, string $estado): int => (int) $filas->where('estado', $estado)->sum('total');

        $totales = collect(self::ESTADOS_CITA)->map(fn ($nombre, $estado) => $contar($filas, $estado));

        $porVeterinario = User::query()
            ->whereIn('id', $filas->pluck('veterinario_id')->unique())
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(function (User $veterinario) use ($filas, $contar): array {
                $suyas = $filas->where('veterinario_id', $veterinario->id);

                return [
                    'nombre' => $veterinario->nombre,
                    'estados' => collect(self::ESTADOS_CITA)->map(fn ($nombre, $estado) => $contar($suyas, $estado)),
                    'total' => (int) $suyas->sum('total'),
                ];
            });

        return view('reportes.citas', [
            'desde' => $desde,
            'hasta' => $hasta,
            'estados' => self::ESTADOS_CITA,
            'totales' => $totales,
            'total' => $totales->sum(),
            'asistencia' => $this->porcentajeAsistencia($totales['atendida'], $totales['no_asistio']),
            'porVeterinario' => $porVeterinario,
        ]);
    }

    /**
     * Reporte de consultas atendidas por cada veterinario en un rango de fechas.
     *
     * @param  Request  $request  Petición con el rango de fechas opcional.
     * @return View Vista con las consultas por veterinario.
     */
    public function consultas(Request $request): View
    {
        $this->permitir($request, self::ROLES_GESTION);
        [$desde, $hasta] = $this->rangoFechas($request);

        $filas = Consulta::query()
            ->whereBetween('fecha_consulta', [$desde, $hasta])
            ->selectRaw('veterinario_id, COUNT(*) as total')
            ->groupBy('veterinario_id')
            ->get();

        $total = (int) $filas->sum('total');
        $nombres = User::query()->whereIn('id', $filas->pluck('veterinario_id'))->pluck('nombre', 'id');

        $porVeterinario = $filas
            ->map(fn ($fila): array => [
                'nombre' => $nombres[$fila->veterinario_id] ?? 'Usuario eliminado',
                'total' => (int) $fila->total,
                'porcentaje' => $total > 0 ? round($fila->total * 100 / $total) : 0,
            ])
            ->sortByDesc('total')
            ->values();

        return view('reportes.consultas', [
            'desde' => $desde,
            'hasta' => $hasta,
            'total' => $total,
            'porVeterinario' => $porVeterinario,
        ]);
    }

    /**
     * Reporte de los diagnósticos más frecuentes en un rango de fechas.
     *
     * @param  Request  $request  Petición con el rango de fechas opcional.
     * @return View Vista con los 10 diagnósticos más repetidos.
     */
    public function diagnosticos(Request $request): View
    {
        $this->permitir($request, self::ROLES_GESTION);
        [$desde, $hasta] = $this->rangoFechas($request);

        $consultas = Consulta::query()->whereBetween('fecha_consulta', [$desde, $hasta]);
        $total = (clone $consultas)->count();

        // Se agrupan sin distinguir mayúsculas ni espacios al inicio o al final
        $diagnosticos = $consultas
            ->selectRaw('LOWER(TRIM(diagnostico)) as diagnostico_normalizado, COUNT(*) as total')
            ->groupBy('diagnostico_normalizado')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($fila): array => [
                'diagnostico' => ucfirst($fila->diagnostico_normalizado),
                'total' => (int) $fila->total,
                'porcentaje' => $total > 0 ? round($fila->total * 100 / $total) : 0,
            ]);

        return view('reportes.diagnosticos', [
            'desde' => $desde,
            'hasta' => $hasta,
            'total' => $total,
            'diagnosticos' => $diagnosticos,
        ]);
    }

    /**
     * Reporte de vacunas y desparasitaciones próximas a vencer o ya vencidas.
     * Solo considera la aplicación más reciente de cada vacuna o producto por mascota,
     * para no mostrar como pendiente algo que ya se volvió a aplicar.
     *
     * @param  Request  $request  Petición con el tipo (proximas o vencidas) y los días.
     * @return View Vista con la lista de dosis pendientes.
     */
    public function dosis(Request $request): View
    {
        $this->permitir($request, self::ROLES_DOSIS);

        $filtros = $request->validate([
            'estado' => ['nullable', Rule::in(['proximas', 'vencidas'])],
            'dias' => ['nullable', 'integer', Rule::in([7, 15, 30, 60])],
        ]);

        $estado = $filtros['estado'] ?? 'proximas';
        $dias = (int) ($filtros['dias'] ?? 30);
        $hoy = CarbonImmutable::today();
        $limite = $hoy->addDays($dias);

        $vacunas = Vacunacion::query()
            ->with('expediente.mascota.cliente')
            ->whereNotNull('proxima_dosis')
            ->get()
            ->map(fn (Vacunacion $vacuna): array => $this->filaDosis(
                'Vacuna', $vacuna->nombre_vacuna, $vacuna->fecha_aplicacion, $vacuna->proxima_dosis, $vacuna->expediente,
            ));

        $desparasitaciones = Desparasitacion::query()
            ->with('expediente.mascota.cliente')
            ->whereNotNull('proxima_aplicacion')
            ->get()
            ->map(fn (Desparasitacion $registro): array => $this->filaDosis(
                'Desparasitación', $registro->producto, $registro->fecha_aplicacion, $registro->proxima_aplicacion, $registro->expediente,
            ));

        $filas = $vacunas->concat($desparasitaciones)
            // Solo la aplicación más reciente de cada vacuna o producto por mascota
            ->groupBy(fn (array $fila): string => $fila['mascota_id'] . '|' . $fila['tipo'] . '|' . mb_strtolower(trim($fila['nombre'])))
            ->map(fn (Collection $grupo): array => $grupo->sortByDesc(fn (array $fila) => $fila['aplicada']->timestamp)->first())
            ->filter(fn (array $fila): bool => $estado === 'vencidas'
                ? $fila['proxima']->lt($hoy)
                : $fila['proxima']->betweenIncluded($hoy, $limite))
            ->sortBy(fn (array $fila) => $fila['proxima']->timestamp)
            ->values();

        return view('reportes.dosis', [
            'filas' => $filas,
            'estado' => $estado,
            'dias' => $dias,
            'hoy' => $hoy,
            'limite' => $limite,
        ]);
    }

    /**
     * Arma una fila del reporte de dosis con los datos de la mascota y del dueño.
     */
    private function filaDosis(string $tipo, string $nombre, $aplicada, $proxima, $expediente): array
    {
        $mascota = $expediente->mascota;

        return [
            'tipo' => $tipo,
            'nombre' => $nombre,
            'aplicada' => CarbonImmutable::parse($aplicada),
            'proxima' => CarbonImmutable::parse($proxima)->startOfDay(),
            'mascota_id' => $mascota->id,
            'mascota' => $mascota->nombre,
            'cliente' => $mascota->cliente->nombre,
            'telefono' => $mascota->cliente->telefono,
        ];
    }

    /**
     * Valida el rango de fechas; por defecto, desde el primer día del mes hasta hoy.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} Inicio y fin del rango.
     */
    private function rangoFechas(Request $request): array
    {
        $filtros = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);

        $desde = isset($filtros['desde'])
            ? CarbonImmutable::parse($filtros['desde'])->startOfDay()
            : CarbonImmutable::today()->startOfMonth();

        $hasta = isset($filtros['hasta'])
            ? CarbonImmutable::parse($filtros['hasta'])->endOfDay()
            : CarbonImmutable::today()->endOfDay();

        return [$desde, $hasta];
    }

    /**
     * Porcentaje de citas atendidas entre las que ya debieron ocurrir (atendidas + no asistió).
     */
    private function porcentajeAsistencia(int $atendidas, int $noAsistio): ?int
    {
        $base = $atendidas + $noAsistio;

        return $base > 0 ? (int) round($atendidas * 100 / $base) : null;
    }

    /**
     * Indica si el usuario tiene alguno de los roles indicados.
     */
    private function tieneRol(Request $request, array $roles): bool
    {
        return in_array($request->user()?->role?->nombre, $roles, true);
    }

    /**
     * Detiene la petición con 403 si el usuario no tiene alguno de los roles indicados.
     */
    private function permitir(Request $request, array $roles): void
    {
        abort_unless($this->tieneRol($request, $roles), 403, 'No tienes permiso para ver este reporte.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Consulta;
use App\Models\Desparasitacion;
use App\Models\Mascota;
use App\Models\Producto;
use App\Models\Vacunacion;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Página de inicio con los indicadores del día: citas, pacientes, inventario y ventas.
 * El veterinario ve solo sus propias citas; los demás roles ven las de todos.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class DashboardController extends Controller
{
    /**
     * Días hacia adelante para las alertas de vacunas y desparasitaciones.
     */
    private const DIAS_DOSIS = 7;

    /**
     * Días hacia adelante para la alerta de productos por vencer.
     */
    private const DIAS_VENCIMIENTO = 30;

    public function __invoke(Request $request): View
    {
        $usuario = $request->user();
        $esVeterinario = $usuario?->role?->nombre === 'Veterinario';
        $hoy = today();

        // Citas de hoy (sin contar las canceladas); el veterinario solo ve las suyas
        $citasHoy = Cita::query()
            ->whereDate('fecha_cita', $hoy)
            ->where('estado', '!=', 'cancelada')
            ->when($esVeterinario, fn (Builder $query) => $query->where('veterinario_id', $usuario->id));

        $proximasCitas = (clone $citasHoy)
            ->with(['mascota.cliente', 'veterinario'])
            ->where('estado', 'pendiente')
            ->where('fecha_cita', '>=', now())
            ->orderBy('fecha_cita')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'esVeterinario' => $esVeterinario,
            'citasHoy' => (clone $citasHoy)->count(),
            'citasPendientesHoy' => (clone $citasHoy)->where('estado', 'pendiente')->count(),
            'proximasCitas' => $proximasCitas,
            'mascotas' => Mascota::query()->count(),
            'clientes' => Cliente::query()->count(),
            'stockBajo' => Producto::query()->activos()->stockBajo()->count(),
            'porVencer' => Producto::query()->activos()->porVencer(self::DIAS_VENCIMIENTO)->count(),
            'ventasHoyTotal' => (float) Venta::query()->whereDate('fecha_venta', $hoy)->sum('total'),
            'ventasHoyCantidad' => Venta::query()->whereDate('fecha_venta', $hoy)->count(),
            'consultasMes' => Consulta::query()
                ->whereBetween('fecha_consulta', [$hoy->copy()->startOfMonth(), now()])
                ->when($esVeterinario, fn (Builder $query) => $query->where('veterinario_id', $usuario->id))
                ->count(),
            'dosisProximas' => $this->dosisProximas(),
            'diasDosis' => self::DIAS_DOSIS,
            'diasVencimiento' => self::DIAS_VENCIMIENTO,
        ]);
    }

    /**
     * Cuenta las vacunas y desparasitaciones que vencen en los próximos días,
     * sin contar las que ya se volvieron a aplicar (hay una aplicación posterior).
     */
    private function dosisProximas(): int
    {
        $desde = today();
        $hasta = today()->addDays(self::DIAS_DOSIS);

        $vacunas = Vacunacion::query()
            ->whereBetween('proxima_dosis', [$desde, $hasta])
            ->whereNotExists(fn (QueryBuilder $query) => $query
                ->from('vacunaciones as posterior')
                ->whereColumn('posterior.expediente_id', 'vacunaciones.expediente_id')
                ->whereColumn('posterior.nombre_vacuna', 'vacunaciones.nombre_vacuna')
                ->whereColumn('posterior.fecha_aplicacion', '>', 'vacunaciones.fecha_aplicacion'))
            ->count();

        $desparasitaciones = Desparasitacion::query()
            ->whereBetween('proxima_aplicacion', [$desde, $hasta])
            ->whereNotExists(fn (QueryBuilder $query) => $query
                ->from('desparasitaciones as posterior')
                ->whereColumn('posterior.expediente_id', 'desparasitaciones.expediente_id')
                ->whereColumn('posterior.producto', 'desparasitaciones.producto')
                ->whereColumn('posterior.fecha_aplicacion', '>', 'desparasitaciones.fecha_aplicacion'))
            ->count();

        return $vacunas + $desparasitaciones;
    }
}
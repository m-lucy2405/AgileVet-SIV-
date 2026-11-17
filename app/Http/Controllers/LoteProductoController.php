<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\LoteProducto;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Corrige la fecha de caducidad de un lote (solo el administrador; lo controlan las rutas).
 * Cada corrección queda en la auditoría con la fecha anterior y la nueva.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
class LoteProductoController extends Controller
{
    public function actualizarCaducidad(Request $request, LoteProducto $lote): RedirectResponse
    {
        $validated = $request->validate([
            'fecha_caducidad' => ['nullable', 'date'],
        ], [
            'fecha_caducidad.date' => 'Escribe una fecha de caducidad válida.',
        ]);

        $anterior = $lote->fecha_caducidad?->toDateString();
        $nueva = isset($validated['fecha_caducidad'])
            ? CarbonImmutable::parse($validated['fecha_caducidad'])->toDateString()
            : null;

        if ($anterior === $nueva) {
            return redirect()
                ->route('productos.edit', $lote->producto_id)
                ->with('status', "La fecha del lote «{$lote->codigo_lote}» no cambió.");
        }

        DB::transaction(function () use ($request, $lote, $anterior, $nueva): void {
            $lote->update(['fecha_caducidad' => $nueva]);

            Auditoria::query()->create([
                'usuario_id' => $request->user()->id,
                'accion' => Auditoria::MODIFICAR,
                'tabla_afectada' => 'lotes_producto',
                'registro_id' => $lote->id,
                'fecha_accion' => now(),
                'descripcion' => "Corrección de la fecha de caducidad del lote {$lote->codigo_lote}.",
                'valores_anteriores' => ['fecha_caducidad' => $anterior],
                'valores_nuevos' => ['fecha_caducidad' => $nueva],
                'ip' => $request->ip(),
            ]);
        });

        return redirect()
            ->route('productos.edit', $lote->producto_id)
            ->with('status', "Se corrigió la fecha del lote «{$lote->codigo_lote}».");
    }
}
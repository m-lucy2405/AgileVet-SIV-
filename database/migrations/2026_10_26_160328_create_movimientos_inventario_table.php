<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial (kárdex) de entradas, salidas y ajustes de cada producto.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->restrictOnDelete();

            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // entrada, salida o ajuste
            $table->string('tipo', 20);

            $table->unsignedInteger('cantidad');

            $table->unsignedInteger('stock_anterior');

            $table->unsignedInteger('stock_nuevo');

            $table->string('motivo', 255);

            $table->dateTime('fecha_movimiento');

            $table->timestamps();

            // Para mostrar rápido el kárdex de un producto ordenado por fecha
            $table->index(['producto_id', 'fecha_movimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
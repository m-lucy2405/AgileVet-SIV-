<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de productos del inventario.
 * El stock solo cambia mediante movimientos de inventario.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('categoria_id')
                  ->constrained('categorias_productos')
                  ->restrictOnDelete();

            // Código interno o de barras
            $table->string('codigo', 50)
                  ->unique();

            $table->string('nombre', 150)
                  ->index();

            $table->text('descripcion')
                  ->nullable();

            // Ej. unidad, caja, frasco, kg
            $table->string('unidad', 30);

            $table->decimal('precio_compra', 10, 2);

            $table->decimal('precio_venta', 10, 2);

            // Existencias actuales: nunca negativas
            $table->unsignedInteger('stock')
                  ->default(0);

            // Al llegar a este número se avisa que hay que reponer
            $table->unsignedInteger('stock_minimo')
                  ->default(0);

            // Importante para medicamentos y vacunas
            $table->date('fecha_vencimiento')
                  ->nullable();

            // En lugar de borrar, se desactiva para conservar el historial
            $table->boolean('activo')
                  ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
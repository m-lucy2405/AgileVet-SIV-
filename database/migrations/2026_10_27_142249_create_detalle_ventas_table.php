<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create historical sale lines with product and lot snapshots.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('lote_producto_id')->constrained('lotes_producto')->restrictOnDelete();
            $table->string('codigo_producto', 50);
            $table->string('nombre_producto', 150);
            $table->string('codigo_lote', 100);
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index(['venta_id', 'id']);
        });
    }

    /**
     * Remove the sale details table.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};

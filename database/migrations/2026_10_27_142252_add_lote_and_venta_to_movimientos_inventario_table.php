<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add optional lot and sale references to inventory movements.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->foreignId('lote_producto_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('lotes_producto')
                ->restrictOnDelete();
            $table->foreignId('venta_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('ventas')
                ->restrictOnDelete();
        });
    }

    /**
     * Remove the lot and sale references from inventory movements.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venta_id');
            $table->dropConstrainedForeignId('lote_producto_id');
        });
    }
};

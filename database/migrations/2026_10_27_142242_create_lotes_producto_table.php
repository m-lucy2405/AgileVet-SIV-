<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create lot tracking and migrate current product stock into legacy lots.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */

    /**
     * Create the lot table and backfill existing product inventory.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function up(): void
    {
        Schema::create('lotes_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('codigo_lote', 100);
            $table->date('fecha_caducidad')->nullable();
            $table->unsignedInteger('cantidad_disponible')->default(0);
            $table->decimal('costo_compra', 10, 2);
            $table->timestamps();

            $table->unique(['producto_id', 'codigo_lote']);
            $table->index(
                ['producto_id', 'fecha_caducidad', 'cantidad_disponible'],
                'lotes_producto_producto_caducidad_stock_idx'
            );
        });

        DB::table('productos')
            ->where('stock', '>', 0)
            ->orderBy('id')
            ->chunkById(500, function ($productos): void {
                foreach ($productos as $producto) {
                    DB::table('lotes_producto')->insert([
                        'producto_id' => $producto->id,
                        'codigo_lote' => 'LEGACY-'.$producto->id,
                        'fecha_caducidad' => $producto->fecha_vencimiento,
                        'cantidad_disponible' => $producto->stock,
                        'costo_compra' => $producto->precio_compra,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Remove the lot table.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes_producto');
    }
};

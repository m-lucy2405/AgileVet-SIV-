<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the table that stores confirmed counter sales.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 40)->unique();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('metodo_pago', 20);
            $table->dateTime('fecha_venta');
            $table->decimal('total', 12, 2);
            $table->timestamps();

            $table->index(['fecha_venta', 'id']);
        });
    }

    /**
     * Remove the sales table.
     *
     * @author Miguel Alfredo Ayala Rodriguez <desarrollo@agilevet.com>
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorías para agrupar los productos del inventario.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_productos', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 100)
                  ->unique();

            $table->text('descripcion')
                  ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_productos');
    }
};
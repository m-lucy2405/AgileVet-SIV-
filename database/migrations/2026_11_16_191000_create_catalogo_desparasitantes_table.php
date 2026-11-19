<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de desparasitantes con su intervalo de reaplicación.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_desparasitantes', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150)
                  ->unique();

            // Especie a la que aplica (Canino, Felino...); vacío = todas
            $table->string('especie', 50)
                  ->nullable();

            // Dosis que se sugiere en el formulario (se puede cambiar)
            $table->string('dosis_sugerida', 100)
                  ->nullable();

            // Cada cuántos días se vuelve a aplicar
            $table->unsignedSmallInteger('intervalo_dias');

            $table->boolean('activo')
                  ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_desparasitantes');
    }
};
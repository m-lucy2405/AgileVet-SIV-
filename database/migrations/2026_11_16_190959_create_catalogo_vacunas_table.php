<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de vacunas con su intervalo de refuerzo.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_vacunas', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150)
                  ->unique();

            // Especie a la que aplica (Canino, Felino...); vacío = todas
            $table->string('especie', 50)
                  ->nullable();

            // Cada cuántos días se aplica el refuerzo
            $table->unsignedSmallInteger('intervalo_dias');

            $table->boolean('activo')
                  ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_vacunas');
    }
};
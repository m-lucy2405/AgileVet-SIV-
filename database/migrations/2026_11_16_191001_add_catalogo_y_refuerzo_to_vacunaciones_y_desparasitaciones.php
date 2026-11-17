<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaza cada vacuna y desparasitación con su catálogo
 * y con la cita de refuerzo que se agendó automáticamente.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacunaciones', function (Blueprint $table) {
            $table->foreignId('catalogo_vacuna_id')
                  ->nullable()
                  ->after('expediente_id')
                  ->constrained('catalogo_vacunas')
                  ->nullOnDelete();

            $table->foreignId('cita_refuerzo_id')
                  ->nullable()
                  ->after('proxima_dosis')
                  ->constrained('citas')
                  ->nullOnDelete();
        });

        Schema::table('desparasitaciones', function (Blueprint $table) {
            $table->foreignId('catalogo_desparasitante_id')
                  ->nullable()
                  ->after('expediente_id')
                  ->constrained('catalogo_desparasitantes')
                  ->nullOnDelete();

            $table->foreignId('cita_refuerzo_id')
                  ->nullable()
                  ->after('proxima_aplicacion')
                  ->constrained('citas')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('desparasitaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cita_refuerzo_id');
            $table->dropConstrainedForeignId('catalogo_desparasitante_id');
        });

        Schema::table('vacunaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cita_refuerzo_id');
            $table->dropConstrainedForeignId('catalogo_vacuna_id');
        });
    }
};
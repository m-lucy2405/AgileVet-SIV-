
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {

            // Identificador único de la cita
            $table->id();

            // Relación con la tabla mascotas
            $table->foreignId('mascota_id')
                  ->constrained('mascotas')
                  ->restrictOnDelete();

            // Relación con la tabla users (veterinario)
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Fecha y hora programada de la cita
            $table->dateTime('fecha_hora');

            // Estado actual de la cita
            $table->string('estado', 20);

            // Motivo de la consulta veterinaria
            $table->string('motivo', 255);

            // Observaciones adicionales
            $table->text('observaciones')
                  ->nullable();

            // Fechas de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};




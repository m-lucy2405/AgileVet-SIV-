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
        Schema::create('consultas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('expediente_id')
                  ->constrained('expedientes')
                  ->restrictOnDelete();

            $table->foreignId('veterinario_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->dateTime('fecha_consulta');

            $table->string('motivo', 255);

            $table->decimal('peso_kg', 6, 2)
                  ->nullable();

            $table->decimal('temperatura_c', 4, 1)
                  ->nullable();

            $table->text('diagnostico')
                  ->nullable();

            $table->text('tratamiento_indicado')
                  ->nullable();

            $table->text('observaciones')
                  ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};

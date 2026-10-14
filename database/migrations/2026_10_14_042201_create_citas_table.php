<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('mascota_id')
                  ->constrained('mascotas')
                  ->restrictOnDelete();

            $table->foreignId('veterinario_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->dateTime('fecha_cita');

            $table->string('motivo', 255);

            $table->string('estado', 20);

            $table->text('observaciones')
                  ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};

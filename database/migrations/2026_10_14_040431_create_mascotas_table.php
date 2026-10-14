<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mascotas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->restrictOnDelete();

            $table->string('nombre', 100);

            $table->string('especie', 50);

            $table->string('raza', 100)
                  ->nullable();

            $table->string('sexo', 15);

            $table->date('fecha_nacimiento')
                  ->nullable();

            $table->string('color', 50)
                  ->nullable();

            $table->boolean('esterilizado')
                  ->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mascotas');
    }
};
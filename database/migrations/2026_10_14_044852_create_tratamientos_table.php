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
        Schema::create('tratamientos', function (Blueprint $table) {

            $table->id();

            $table->foreignId('consulta_id')
                  ->constrained('consultas')
                  ->restrictOnDelete();

            $table->string('medicamento', 150);

            $table->string('dosis', 100);

            $table->string('frecuencia', 100);

            $table->string('duracion', 100);

            $table->text('indicaciones')
                  ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tratamientos');
    }
};

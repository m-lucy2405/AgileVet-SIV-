<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desparasitaciones', function (Blueprint $table) {

            $table->id();

            $table->foreignId('expediente_id')
                  ->constrained('expedientes')
                  ->restrictOnDelete();

            $table->string('producto', 150);

            $table->string('dosis', 100);

            $table->date('fecha_aplicacion');

            $table->date('proxima_aplicacion')
                  ->nullable();

            $table->text('observaciones')
                  ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desparasitaciones');
    }
};

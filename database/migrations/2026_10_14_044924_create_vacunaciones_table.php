<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacunaciones', function (Blueprint $table) {

            $table->id();

            $table->foreignId('expediente_id')
                  ->constrained('expedientes')
                  ->restrictOnDelete();

            $table->string('nombre_vacuna', 150);

            $table->date('fecha_aplicacion');

            $table->date('proxima_dosis')
                  ->nullable();

            $table->text('observaciones')
                  ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacunaciones');
    }
};

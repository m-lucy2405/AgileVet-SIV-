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
        Schema::create('auditoria', function (Blueprint $table) {

            $table->id();

            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->string('accion', 100);

            $table->string('tabla_afectada', 100);

            $table->unsignedBigInteger('registro_id')
                  ->nullable();

            $table->dateTime('fecha_accion');

            $table->text('descripcion')
                  ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};

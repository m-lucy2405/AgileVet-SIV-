<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a la auditoría los valores anteriores y nuevos de cada cambio,
 * y la dirección IP desde donde se hizo.
 *
 * @author Equipo de desarrollo AgileVet-SIV
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            // Valores antes del cambio (vacío al crear)
            $table->json('valores_anteriores')
                  ->nullable()
                  ->after('descripcion');

            // Valores después del cambio (vacío al eliminar)
            $table->json('valores_nuevos')
                  ->nullable()
                  ->after('valores_anteriores');

            // Dirección IP del usuario (45 caracteres alcanzan para IPv6)
            $table->string('ip', 45)
                  ->nullable()
                  ->after('valores_nuevos');

            // Para buscar rápido el historial de un registro y ordenar por fecha
            $table->index(['tabla_afectada', 'registro_id']);
            $table->index('fecha_accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->dropIndex(['tabla_afectada', 'registro_id']);
            $table->dropIndex(['fecha_accion']);
            $table->dropColumn(['valores_anteriores', 'valores_nuevos', 'ip']);
        });
    }
};
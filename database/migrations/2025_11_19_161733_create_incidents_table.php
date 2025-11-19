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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            
            // Relación: Un incidente pertenece a un Reporte
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();

            // --- Identificación de la Falla ---
            $table->string('identificador'); // Ej: "Arpón 13-8", "Puerto 9-10"
            $table->text('barrios');         // Ej: "Urb. Berlín, El Portal, Los Colores"
            
            // --- Detalles del Impacto ---
            $table->integer('usuarios_afectados')->nullable(); // Ej: 47
            
            $table->enum('tipo_falla', [
                'fibra',             // Corte de fibra
                'energia',           // Falla eléctrica
                'potencia',          // Ajustes de potencia
                'equipo_alarmado',   // Puertos alarmados
                'mantenimiento',     // Mantenimiento programado
                'desconocido'
            ])->default('desconocido');

            // --- Estado del Incidente ---
            $table->enum('estado', [
                'pendiente',    // "Continúa sin servicio"
                'en_proceso',   // "Equipo técnico notificado"
                'resuelto'      // "Todos los usuarios en línea"
            ])->default('pendiente');

            // --- Descripción Técnica ---
            $table->text('descripcion')->nullable(); // La explicación larga del reporte

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};

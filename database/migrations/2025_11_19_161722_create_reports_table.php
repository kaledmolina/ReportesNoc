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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            
            // --- 1. Contexto del Reporte ---
            $table->date('fecha');
            $table->enum('turno', ['mañana', 'tarde', 'noche']);
            $table->string('ciudad')->default('Montería');

            // --- 2. Infraestructura General ---
            $table->boolean('concentradores_ok')->default(true); // 1️⃣ Concentradores
            $table->boolean('proveedores_ok')->default(true);    // 2️⃣ Proveedores
            
            // --- 3. Estado OLTs (Temperaturas) ---
            $table->integer('temp_olt_monteria');       // Ej: 29
            $table->integer('temp_olt_backup');         // Ej: 27

            // --- 4. Televisión ---
            $table->integer('tv_canales_activos');      // Ej: 90
            $table->integer('tv_canales_total')->default(92);
            $table->json('tv_canales_offline')->nullable(); // Lista: ["NatGeo", "Oromar"]
            
            // --- 5. Servidores / Streaming ---
            $table->boolean('intalflix_online')->default(true); // Estado Intalflix
            $table->text('novedades_servidores')->nullable();   // Notas extra sobre servidores

            // --- 6. Resumen General de Novedades ---
            $table->text('observaciones_generales')->nullable(); // Texto libre si hace falta

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

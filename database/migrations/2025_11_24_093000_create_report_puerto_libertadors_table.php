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
        Schema::create('report_puerto_libertadors', function (Blueprint $table) {
            $table->id();
            
            // --- 1. Contexto del Reporte ---
            $table->date('fecha');
            $table->enum('turno', ['mañana', 'tarde', 'noche']);
            $table->string('ciudad')->default('Puerto Libertador');

            // --- 2. Infraestructura Específica ---
            $table->boolean('olt_operativa')->default(true);
            $table->string('detalle_olt')->nullable(); // Novedad OLT

            $table->boolean('mikrotik_2116_operativo')->default(true);
            $table->string('detalle_mikrotik')->nullable(); // Novedad Mikrotik

            $table->boolean('enlace_dedicado_operativo')->default(true);
            $table->string('detalle_enlace')->nullable(); // Novedad Enlace

            $table->boolean('servidor_tv_operativo')->default(true);
            $table->string('detalle_tv')->nullable(); // Novedad TV

            $table->boolean('modulador_ip_operativo')->default(true);
            $table->string('detalle_modulador')->nullable(); // Novedad Modulador

            // --- 4. Televisión (Mantengo esto por si acaso, o lo simplifico) ---
            // El usuario mencionó "servidor televisión" como item, pero quizás sigan queriendo contar canales
            $table->integer('tv_canales_activos')->nullable();      
            $table->integer('tv_canales_total')->default(92);
            $table->json('tv_canales_offline')->nullable(); 

            // --- 5. Novedades ---
            $table->text('novedades_servidores')->nullable();   

            // --- 6. Resumen General de Novedades ---
            $table->text('observaciones_generales')->nullable(); 

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_puerto_libertadors');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Columnas para guardar el detalle de tarjetas/puertos de cada OLT
            // Estructura JSON igual a la de incidentes
            $table->json('olt_monteria_detalle')->nullable()->after('temp_olt_monteria');
            $table->json('olt_backup_detalle')->nullable()->after('temp_olt_backup');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['olt_monteria_detalle', 'olt_backup_detalle']);
        });
    }
};
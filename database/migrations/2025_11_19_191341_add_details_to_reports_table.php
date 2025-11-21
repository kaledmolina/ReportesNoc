<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Campo para explicar qué concentradores fallan (Ej: "Concentrador 4 lento")
            $table->text('concentradores_detalle')->nullable()->after('concentradores_ok');
            
            // Campo para notas de canales (Ej: "Discovery en inglés")
            $table->text('tv_observaciones')->nullable()->after('tv_canales_offline');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['concentradores_detalle', 'tv_observaciones']);
        });
    }
};
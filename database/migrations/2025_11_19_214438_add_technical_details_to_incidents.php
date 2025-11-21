<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Campos específicos para OLT
            $table->string('olt_nombre')->nullable(); // 'Main' o 'Backup'
            $table->integer('olt_tarjeta')->nullable(); // 1 al 17
            $table->integer('olt_puerto')->nullable();  // 1 al 16
            
            // Campos específicos para TV
            $table->json('tv_canales_afectados')->nullable(); // Lista de canales
            
            // Hacemos que 'identificador' sea nullable (opcional) porque
            // ahora lo calcularemos automáticamente para OLT y TV
            $table->string('identificador')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['olt_nombre', 'olt_tarjeta', 'olt_puerto', 'tv_canales_afectados']);
            // No podemos revertir 'identificador' a not null fácilmente si hay datos nulos, 
            // así que lo dejamos así por seguridad.
        });
    }
};
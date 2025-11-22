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
    Schema::table('incidents', function (Blueprint $table) {
        // Cambiamos a string con longitud suficiente (ej. 100)
        // Asegúrate de tener doctrine/dbal instalado si usas Laravel < 10, 
        // pero en Laravel 11/12 es nativo.
        $table->string('tipo_falla', 100)->change();
    });
}

public function down(): void
{
    // Si necesitas revertir (aunque no te lo recomiendo)
    // tendrías que volver a listar tus enums aquí
    // $table->enum('tipo_falla', ['falla_olt', 'otra_falla'])->change();
}
};

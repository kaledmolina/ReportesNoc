<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Creamos columnas JSON para guardar la lista completa con sus estados
            // Ejemplo guardado: [{"nombre": "Concentrador 1", "estado": true, "detalle": ""}, ...]
            $table->json('lista_concentradores')->nullable()->after('concentradores_detalle');
            $table->json('lista_proveedores')->nullable()->after('proveedores_ok');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['lista_concentradores', 'lista_proveedores']);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Guardará la lista: [{"nombre": "Servidor Zabbix", "estado": true, "detalle": null}, ...]
            $table->json('lista_servidores')->nullable()->after('novedades_servidores');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('lista_servidores');
        });
    }
};
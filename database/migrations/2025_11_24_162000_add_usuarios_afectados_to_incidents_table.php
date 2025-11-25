<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Hacemos nullable 'barrios' ya que se eliminó del formulario
            $table->text('barrios')->nullable()->change();
            
            // Eliminamos la columna antigua (integer) para crear la nueva (json)
            $table->dropColumn('usuarios_afectados');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->json('usuarios_afectados')->nullable()->after('olt_afectacion');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('usuarios_afectados');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->integer('usuarios_afectados')->nullable();
            $table->text('barrios')->nullable(false)->change();
        });
    }
};

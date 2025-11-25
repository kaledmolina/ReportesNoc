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
            $table->foreignId('report_puerto_libertador_id')->nullable()->constrained()->nullOnDelete();
            // Hacemos report_id nullable para permitir incidentes que solo pertenezcan a Puerto Libertador
            $table->unsignedBigInteger('report_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['report_puerto_libertador_id']);
            $table->dropColumn('report_puerto_libertador_id');
            // No podemos revertir fácilmente report_id a not null sin saber si hay datos nulos, 
            // pero en un down idealmente deberíamos limpiar datos o revertir la restricción si es seguro.
            // Por seguridad, lo dejamos nullable o requeriría lógica extra.
        });
    }
};

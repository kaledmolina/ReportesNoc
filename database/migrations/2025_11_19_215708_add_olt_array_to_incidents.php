<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Guardará la estructura compleja: 
            // [ { "tarjeta": 1, "puertos": [1,2,3] }, { "tarjeta": 5, "puertos": [1] } ]
            $table->json('olt_afectacion')->nullable()->after('olt_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('olt_afectacion');
        });
    }
};
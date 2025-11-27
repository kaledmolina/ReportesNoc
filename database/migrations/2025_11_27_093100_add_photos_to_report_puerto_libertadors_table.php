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
        Schema::table('report_puerto_libertadors', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('observaciones_generales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_puerto_libertadors', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};

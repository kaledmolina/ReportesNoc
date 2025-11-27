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
        Schema::table('report_regionals', function (Blueprint $table) {
            $table->json('photos_valencia')->nullable()->after('detalle_valencia_servidor_vmix');
            $table->json('photos_tierralta')->nullable()->after('detalle_tierralta_enlace_ancla');
            $table->json('photos_san_pedro')->nullable()->after('detalle_san_pedro_mikrotik_1036');
            
            // Drop the generic photos column if it exists (from previous step)
            if (Schema::hasColumn('report_regionals', 'photos')) {
                $table->dropColumn('photos');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_regionals', function (Blueprint $table) {
            $table->dropColumn(['photos_valencia', 'photos_tierralta', 'photos_san_pedro']);
            $table->json('photos')->nullable();
        });
    }
};

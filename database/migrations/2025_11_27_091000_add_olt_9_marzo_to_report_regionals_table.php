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
            $table->boolean('tierralta_olt_9_marzo_operativa')->default(true)->after('detalle_tierralta_olt');
            $table->string('detalle_tierralta_olt_9_marzo')->nullable()->after('tierralta_olt_9_marzo_operativa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_regionals', function (Blueprint $table) {
            $table->dropColumn(['tierralta_olt_9_marzo_operativa', 'detalle_tierralta_olt_9_marzo']);
        });
    }
};

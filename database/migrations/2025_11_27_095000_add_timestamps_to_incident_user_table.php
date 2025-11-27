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
        Schema::table('incident_user', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->after('rejected_at');
            $table->timestamp('escalated_at')->nullable()->after('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incident_user', function (Blueprint $table) {
            $table->dropColumn(['resolved_at', 'escalated_at']);
        });
    }
};

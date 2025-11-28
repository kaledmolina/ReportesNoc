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
            // Change enum to string to allow any status (like 'escalated', 'resolved')
            // and remove the CHECK constraint in SQLite.
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incident_user', function (Blueprint $table) {
            // Revert back to enum if needed (might fail if data contains invalid values)
            // For now, we can just leave it as string or try to revert.
            // $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->change();
        });
    }
};

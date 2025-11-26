<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add ticket_number to incidents
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('ticket_number')->nullable()->unique()->after('id');
        });

        // 2. Create pivot table for assignments
        Schema::create('incident_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // The responsible
            
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            
            $table->foreignId('assigned_by')->nullable()->constrained('users'); // Who assigned it
            $table->text('notes')->nullable(); // Rejection reason or escalation notes
            
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_user');
        
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('ticket_number');
        });
    }
};

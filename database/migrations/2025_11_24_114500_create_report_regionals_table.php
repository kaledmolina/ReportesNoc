<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_regionals', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('turno'); // mañana, tarde, noche
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // --- VALENCIA ---
            $table->boolean('valencia_bgp_2116_operativo')->default(true);
            $table->string('detalle_valencia_bgp_2116')->nullable();
            
            $table->boolean('valencia_olt_swifts_operativa')->default(true);
            $table->string('detalle_valencia_olt_swifts')->nullable();
            
            $table->boolean('valencia_mikrotik_1036_operativo')->default(true);
            $table->string('detalle_valencia_mikrotik_1036')->nullable();
            
            $table->boolean('valencia_servidor_tv_operativo')->default(true);
            $table->string('detalle_valencia_servidor_tv')->nullable();
            
            $table->boolean('valencia_modulador_ip_operativo')->default(true);
            $table->string('detalle_valencia_modulador_ip')->nullable();
            
            $table->boolean('valencia_servidor_intalflix_operativo')->default(true);
            $table->string('detalle_valencia_servidor_intalflix')->nullable();
            
            $table->boolean('valencia_servidor_vmix_operativo')->default(true);
            $table->string('detalle_valencia_servidor_vmix')->nullable();

            // --- TIERRALTA ---
            $table->boolean('tierralta_olt_operativa')->default(true);
            $table->string('detalle_tierralta_olt')->nullable();
            
            $table->boolean('tierralta_mikrotik_1036_operativo')->default(true);
            $table->string('detalle_tierralta_mikrotik_1036')->nullable();
            
            $table->boolean('tierralta_mikrotik_fomento_operativo')->default(true);
            $table->string('detalle_tierralta_mikrotik_fomento')->nullable();
            
            $table->boolean('tierralta_enlace_urra_operativo')->default(true);
            $table->string('detalle_tierralta_enlace_urra')->nullable();
            
            $table->boolean('tierralta_enlace_ancla_operativo')->default(true);
            $table->string('detalle_tierralta_enlace_ancla')->nullable();

            // --- SAN PEDRO ---
            $table->boolean('san_pedro_olt_operativa')->default(true);
            $table->string('detalle_san_pedro_olt')->nullable();
            
            $table->boolean('san_pedro_mikrotik_1036_operativo')->default(true);
            $table->string('detalle_san_pedro_mikrotik_1036')->nullable();

            $table->text('observaciones_generales')->nullable();
            $table->timestamps();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->foreignId('report_regional_id')->nullable()->constrained('report_regionals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['report_regional_id']);
            $table->dropColumn('report_regional_id');
        });
        Schema::dropIfExists('report_regionals');
    }
};
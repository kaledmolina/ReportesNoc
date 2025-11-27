<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportRegional extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        // Valencia
        'valencia_bgp_2116_operativo' => 'boolean',
        'valencia_olt_swifts_operativa' => 'boolean',
        'valencia_mikrotik_1036_operativo' => 'boolean',
        'valencia_servidor_tv_operativo' => 'boolean',
        'valencia_modulador_ip_operativo' => 'boolean',
        'valencia_servidor_intalflix_operativo' => 'boolean',
        'valencia_servidor_vmix_operativo' => 'boolean',
        // Tierralta
        'tierralta_olt_operativa' => 'boolean',
        'tierralta_olt_9_marzo_operativa' => 'boolean',
        'tierralta_mikrotik_1036_operativo' => 'boolean',
        'tierralta_mikrotik_fomento_operativo' => 'boolean',
        'tierralta_enlace_urra_operativo' => 'boolean',
        'tierralta_enlace_ancla_operativo' => 'boolean',
        // San Pedro
        'san_pedro_olt_operativa' => 'boolean',
        'san_pedro_mikrotik_1036_operativo' => 'boolean',
        'photos_valencia' => 'array',
        'photos_tierralta' => 'array',
        'photos_san_pedro' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    protected static function booted()
    {
        static::deleting(function ($report) {
            if ($report->incidents()->exists()) {
                \Filament\Notifications\Notification::make()
                    ->title('Operación Denegada')
                    ->body('No se puede eliminar el reporte porque tiene incidentes asociados.')
                    ->danger()
                    ->send();
                
                return false;
            }
        });
    }
}

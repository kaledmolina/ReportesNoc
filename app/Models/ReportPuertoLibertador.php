<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportPuertoLibertador extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'tv_canales_offline' => 'array',
        'olt_operativa' => 'boolean',
        'mikrotik_2116_operativo' => 'boolean',
        'enlace_dedicado_operativo' => 'boolean',
        'servidor_tv_operativo' => 'boolean',
        'modulador_ip_operativo' => 'boolean',
        'photos' => 'array',
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

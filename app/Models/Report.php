<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'tv_canales_offline' => 'array',
        'concentradores_ok' => 'boolean',
        'proveedores_ok' => 'boolean',
        // 'intalflix_online' => 'boolean',  <-- ELIMINADO
        'lista_concentradores' => 'array',
        'lista_proveedores' => 'array',
        'olt_monteria_detalle' => 'array', 
        'olt_backup_detalle' => 'array',
        // --- NUEVO CAMPO ---
        'lista_servidores' => 'array', 
        'photos' => 'array',
    ];

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
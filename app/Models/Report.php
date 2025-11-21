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
    ];

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
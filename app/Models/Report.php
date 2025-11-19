<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    // Permitir guardar todos los campos definidos en la migración
    protected $guarded = [];

    // Casteos automáticos: Convierte datos de BD a tipos PHP útiles
    protected $casts = [
        'fecha' => 'date',
        'tv_canales_offline' => 'array', // Convierte el JSON a Array automáticamente
        'concentradores_ok' => 'boolean',
        'proveedores_ok' => 'boolean',
        'intalflix_online' => 'boolean',
    ];

    // Relación: Un reporte tiene muchos incidentes
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
    
    // Helper: Calcula el porcentaje de canales activos automáticamente
    public function getPorcentajeTvAttribute()
    {
        if ($this->tv_canales_total == 0) return 0;
        return round(($this->tv_canales_activos / $this->tv_canales_total) * 100, 1);
    }
}
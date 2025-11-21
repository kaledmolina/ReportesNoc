<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tv_canales_afectados' => 'array',
        'olt_afectacion' => 'array', // ¡Nueva lista de tarjetas!
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
    
    // ACCESOR MÁGICO ACTUALIZADO:
    protected static function booted()
    {
        static::saving(function ($incident) {
            // Generador de Nombres Automático
            if ($incident->tipo_falla === 'falla_olt') {
                // Formatear lista compleja: "OLT Main - T1:[1,2] | T5:[All]"
                $detalles = collect($incident->olt_afectacion ?? [])
                    ->map(function ($item) {
                        $puertos = implode(',', $item['puertos'] ?? []);
                        return "T{$item['tarjeta']}:[{$puertos}]";
                    })
                    ->join(' | ');
                
                $incident->identificador = "OLT {$incident->olt_nombre} - {$detalles}";
            
            } elseif ($incident->tipo_falla === 'falla_tv') {
                $incident->identificador = "Servidor de TV";
            }
        });
    }
}
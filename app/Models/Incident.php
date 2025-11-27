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
        'usuarios_afectados' => 'array',
        'photos_creation' => 'array',
        'photos_resolution' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($incident) {
            // Generar Ticket ID: TKT-{Ymd}-{Random4}
            $incident->ticket_number = 'TKT-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
            
            if (auth()->check()) {
                $incident->created_by = auth()->id();
            }
        });

        static::saving(function ($incident) {
            // Generador de Nombres Automático
            if ($incident->tipo_falla === 'falla_olt') {
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


    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function reportPuertoLibertador(): BelongsTo
    {
        return $this->belongsTo(ReportPuertoLibertador::class);
    }

    public function reportRegional(): BelongsTo
    {
        return $this->belongsTo(ReportRegional::class);
    }

    public function responsibles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'incident_user')
            ->withPivot(['status', 'assigned_by', 'notes', 'assigned_at', 'accepted_at', 'rejected_at'])
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
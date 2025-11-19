<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    protected $guarded = [];

    // Relación inversa: Un incidente pertenece a un reporte
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
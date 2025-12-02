<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentComment extends Model
{
    protected $guarded = [];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

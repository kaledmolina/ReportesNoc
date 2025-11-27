<?php

namespace App\Filament\Resources\AssignedIncidentResource\Pages;

use App\Filament\Resources\AssignedIncidentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAssignedIncident extends ViewRecord
{
    protected static string $resource = AssignedIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

<?php

namespace App\Filament\Resources\AssignedIncidentResource\Pages;

use App\Filament\Resources\AssignedIncidentResource;
use Filament\Resources\Pages\ListRecords;

class ListAssignedIncidents extends ListRecords
{
    protected static string $resource = AssignedIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

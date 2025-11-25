<?php

namespace App\Filament\Resources\ReportRegionalResource\Pages;

use App\Filament\Resources\ReportRegionalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportRegionals extends ListRecords
{
    protected static string $resource = ReportRegionalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ReportPuertoLibertadorResource\Pages;

use App\Filament\Resources\ReportPuertoLibertadorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportPuertoLibertadors extends ListRecords
{
    protected static string $resource = ReportPuertoLibertadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ReportPuertoLibertadorHistoryResource\Pages;

use App\Filament\Resources\ReportPuertoLibertadorHistoryResource;
use Filament\Resources\Pages\ListRecords;

class ListReportPuertoLibertadorHistories extends ListRecords
{
    protected static string $resource = ReportPuertoLibertadorHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

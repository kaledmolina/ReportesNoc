<?php

namespace App\Filament\Resources\ReportHistoryResource\Pages;

use App\Filament\Resources\ReportHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportHistories extends ListRecords
{
    protected static string $resource = ReportHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

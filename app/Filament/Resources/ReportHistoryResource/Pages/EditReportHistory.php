<?php

namespace App\Filament\Resources\ReportHistoryResource\Pages;

use App\Filament\Resources\ReportHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReportHistory extends EditRecord
{
    protected static string $resource = ReportHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ReportPuertoLibertadorResource\Pages;

use App\Filament\Resources\ReportPuertoLibertadorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReportPuertoLibertador extends EditRecord
{
    protected static string $resource = ReportPuertoLibertadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

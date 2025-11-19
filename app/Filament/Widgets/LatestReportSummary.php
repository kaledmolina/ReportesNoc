<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\Widget;

class LatestReportSummary extends Widget
{
    // Ocupar todo el ancho del dashboard para que se lea bien
    protected int | string | array $columnSpan = 'full';
    
    // Título de la caja
    protected static ?string $heading = 'Resumen Ejecutivo del Último Turno';

    // Ruta de la vista que crearemos a continuación
    protected static string $view = 'filament.widgets.latest-report-summary';

    // Pasamos los datos a la vista
    protected function getViewData(): array
    {
        return [
            'report' => Report::with('incidents') // Traemos también los incidentes
                        ->latest('created_at')
                        ->first(),
        ];
    }
}
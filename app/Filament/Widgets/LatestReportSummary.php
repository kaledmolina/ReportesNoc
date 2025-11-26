<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\Widget;

class LatestReportSummary extends Widget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Resumen Ejecutivo del Último Turno';
    protected static string $view = 'filament.widgets.latest-report-summary';

    public static function canView(): bool
    {
        return auth()->user()->can('view_widget_latest_report') || auth()->user()->hasRole('super_admin');
    }
    
    // Mantenemos el orden que solicitaste
    protected static ?int $sort = 7; 

    protected function getViewData(): array
    {
        return [
            'report' => Report::withCount('incidents') // Cargamos el conteo para mostrarlo en la cabecera
                        ->latest('created_at')
                        ->first(),
        ];
    }
}
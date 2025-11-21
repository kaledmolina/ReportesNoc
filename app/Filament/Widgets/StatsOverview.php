<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use App\Models\Incident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    protected static ?int $sort = 1; // Que aparezca primero

    protected function getStats(): array
    {
        Carbon::setLocale('es');

        // 1. TICKET METRICS
        $pendientes = Incident::where('estado', 'pendiente')->count();
        $enProceso = Incident::where('estado', 'en_proceso')->count();
        $resueltosHoy = Incident::where('estado', 'resuelto')
            ->whereDate('updated_at', Carbon::today())
            ->count();

        // 2. INFRAESTRUCTURA CRÍTICA (Último reporte)
        $ultimoReporte = Report::latest('created_at')->first();
        $tempOlt = $ultimoReporte ? $ultimoReporte->temp_olt_monteria : 0;
        
        // Alerta visual si hay muchos pendientes
        $colorPendientes = $pendientes > 0 ? 'danger' : 'success';
        $iconoPendientes = $pendientes > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle';

        return [
            Stat::make('Tickets Pendientes', $pendientes)
                ->description('Requieren atención inmediata')
                ->descriptionIcon($iconoPendientes)
                ->color($colorPendientes)
                ->chart([$pendientes, $pendientes, $pendientes + 2, $pendientes]),

            Stat::make('En Seguimiento', $enProceso)
                ->description('Tickets en proceso de solución')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),

            Stat::make('Solucionados Hoy', $resueltosHoy)
                ->description('Efectividad del día')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Temp. OLT Main', $tempOlt . '°C')
                ->description($ultimoReporte ? 'Actualizado: ' . $ultimoReporte->created_at->diffForHumans() : 'Sin datos')
                ->color($tempOlt > 32 ? 'danger' : 'info'),
        ];
    }
}
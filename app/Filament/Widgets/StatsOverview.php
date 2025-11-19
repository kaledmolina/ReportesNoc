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

    protected function getStats(): array
    {
        Carbon::setLocale('es');

        // Obtenemos el último reporte
        $ultimoReporte = Report::latest('created_at')->first();

        if (!$ultimoReporte) {
            return [Stat::make('Sin datos', 'Registra tu primer reporte')];
        }

        // --- LÓGICA DE TIEMPO (Igual que antes) ---
        $tiempoTranscurrido = $ultimoReporte->created_at->diffForHumans();
        $horasDiferencia = $ultimoReporte->created_at->diffInHours(now());

        $colorTiempo = match (true) {
            $horasDiferencia >= 8 => 'danger',
            $horasDiferencia >= 4 => 'warning',
            default => 'success',
        };
        $iconoTiempo = $horasDiferencia >= 8 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-clock';

        // --- LÓGICA CORREGIDA PARA CONTAR FALLAS ---
        
        // 1. Contamos los tickets escritos en la lista que no están resueltos
        $ticketsPendientes = Incident::where('estado', '!=', 'resuelto')->count();

        // 2. Sumamos las fallas críticas marcadas en los interruptores del ÚLTIMO reporte
        $fallasCriticas = 0;
        if (!$ultimoReporte->intalflix_online) $fallasCriticas++;   // Suma si Intalflix está caído
        if (!$ultimoReporte->concentradores_ok) $fallasCriticas++;  // Suma si Concentradores fallan
        if (!$ultimoReporte->proveedores_ok) $fallasCriticas++;     // Suma si Proveedores fallan

        // 3. Total real de problemas
        $totalIncidentes = $ticketsPendientes + $fallasCriticas;

        return [
            // TARJETA 1: TIEMPO
            Stat::make('Último Reporte', $tiempoTranscurrido)
                ->description('Turno: ' . ucfirst($ultimoReporte->turno))
                ->descriptionIcon($iconoTiempo)
                ->color($colorTiempo),

            // TARJETA 2: TEMPERATURA
            Stat::make('Temp. OLT Montería', $ultimoReporte->temp_olt_monteria . '°C')
                ->description('OLT Backup: ' . $ultimoReporte->temp_olt_backup . '°C')
                ->descriptionIcon($ultimoReporte->temp_olt_monteria > 35 ? 'heroicon-m-fire' : 'heroicon-m-check-circle')
                ->color($ultimoReporte->temp_olt_monteria > 35 ? 'danger' : 'success')
                ->chart([28, 29, 29, 30, $ultimoReporte->temp_olt_monteria]), 

            // TARJETA 3: TELEVISIÓN (Intalflix)
            Stat::make('Televisión', $ultimoReporte->tv_canales_activos . '/' . $ultimoReporte->tv_canales_total)
                ->label('Canales Activos')
                ->description($ultimoReporte->intalflix_online ? 'Intalflix: En Línea' : 'Intalflix: CAÍDO')
                ->descriptionIcon($ultimoReporte->intalflix_online ? 'heroicon-m-wifi' : 'heroicon-m-exclamation-triangle')
                ->color($ultimoReporte->tv_canales_activos < 90 || !$ultimoReporte->intalflix_online ? 'danger' : 'success'),

            // TARJETA 4: INCIDENTES TOTALES (CORREGIDO)
            Stat::make('Fallas Activas', $totalIncidentes) // Cambié el nombre a "Fallas Activas" para ser más preciso
                ->description($ticketsPendientes . ' Tickets + ' . $fallasCriticas . ' Críticos')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color($totalIncidentes > 0 ? 'danger' : 'gray'),
        ];
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerStatsWidget extends BaseWidget
{
    // Orden 4: Aparece después de Concentradores
    protected static ?int $sort = 4;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $report = Report::latest('created_at')->first();

        if (!$report) {
            return [Stat::make('Infraestructura', 'Sin datos')->color('gray')];
        }

        $stats = [];

        // 1. TEMPERATURAS OLT (Las ponemos primero porque son críticas)
        $stats[] = Stat::make('OLT Main', $report->temp_olt_monteria . '°C')
            ->description('Temperatura Actual')
            ->descriptionIcon('heroicon-m-fire')
            ->color($report->temp_olt_monteria > 45 ? 'danger' : 'warning') // Naranja/Rojo según calor
            ->chart([30, 32, 35, 38, 40, 42, $report->temp_olt_monteria]);

        $stats[] = Stat::make('OLT Backup', $report->temp_olt_backup . '°C')
            ->description('Temperatura Actual')
            ->descriptionIcon('heroicon-m-fire')
            ->color($report->temp_olt_backup > 45 ? 'danger' : 'info') // Azul/Rojo
            ->chart([25, 26, 27, 28, 28, 29, $report->temp_olt_backup]);

        // 2. SERVIDORES
        if (!empty($report->lista_servidores)) {
            foreach ($report->lista_servidores as $s) {
                $nombre = $s['nombre'] ?? 'Srv';
                $estado = $s['estado'] ?? false;
                $detalle = $s['detalle'] ?? null; // Capturamos el detalle
                
                // Lógica de Descripción:
                // Si hay detalle escrito, lo muestra. Si no, muestra estado genérico.
                $descripcion = $detalle ?: ($estado ? 'Operativo' : 'Falla detectada');
                
                $stats[] = Stat::make($nombre, $estado ? 'Online' : 'Offline')
                    ->description($descripcion) // Aquí mostramos la novedad
                    ->descriptionIcon($estado ? 'heroicon-m-server' : 'heroicon-m-x-circle')
                    ->color($estado ? 'success' : 'danger');
            }
        }

        return $stats;
    }
}
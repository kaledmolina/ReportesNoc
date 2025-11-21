<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProviderStatsWidget extends BaseWidget
{
    // Usamos el orden 2 para que salga justo debajo de los contadores de tickets
    protected static ?int $sort = 3; 
    
    // Opcional: Refrescar cada cierto tiempo si lo deseas
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // Obtenemos el último reporte
        $report = Report::latest('created_at')->first();

        // Validación si no hay datos
        if (!$report || empty($report->lista_proveedores)) {
            return [
                Stat::make('Proveedores', 'Sin Información')
                    ->description('Esperando primer reporte')
                    ->color('gray'),
            ];
        }

        $stats = [];

        foreach ($report->lista_proveedores as $proveedor) {
            $nombre = $proveedor['nombre'] ?? 'Proveedor';
            $consumo = $proveedor['consumo'] ?: 'N/A';
            $estado = $proveedor['estado'] ?? false;
            $detalle = $proveedor['detalle'] ?? null;

            // Lógica Visual
            $color = $estado ? 'success' : 'danger';
            $icon = $estado ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle';
            
            // Descripción: Si hay detalle lo muestra, si no pone un texto genérico
            $description = $detalle ?: ($estado ? 'Enlace operativo' : 'Falla crítica');

            // Creamos la Estadistica Nativa
            $stats[] = Stat::make($nombre, $consumo)
                ->description($description)
                ->descriptionIcon($icon)
                ->color($color)
                // Gráfico decorativo (simulado para estética visual como en tu ejemplo)
                ->chart($estado ? [15, 20, 25, 30, 28, 35, 40] : [40, 30, 20, 10, 5, 2, 0]); 
        }

        return $stats;
    }
}
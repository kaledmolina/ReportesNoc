<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConcentratorStatsWidget extends BaseWidget
{
    // Orden: 3 (Para que salga después de los proveedores)
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $report = Report::latest('created_at')->first();

        if (!$report || empty($report->lista_concentradores)) {
            return [
                Stat::make('Concentradores', 'Sin Datos')
                    ->description('No hay reporte reciente')
                    ->color('gray'),
            ];
        }

        $stats = [];

        foreach ($report->lista_concentradores as $c) {
            // Abreviamos el nombre para que quepa mejor (Concentrador 1 -> Conc. 1)
            $nombre = str_replace('Concentrador', 'Conc.', $c['nombre'] ?? 'Conc.');
            $estado = $c['estado'] ?? false;
            $detalle = $c['detalle'] ?? null;

            // Determinar valores visuales
            $valor = $estado ? 'Operativo' : 'Falla';
            $color = $estado ? 'success' : 'danger';
            $icon = $estado ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle';
            
            // Si hay un detalle específico (ej: "Lentitud"), lo usamos como descripción
            // Si no, ponemos un texto genérico
            $descripcion = $detalle ?: ($estado ? 'Sin novedades' : 'Verificar equipo');

            $stats[] = Stat::make($nombre, $valor)
                ->description($descripcion)
                ->descriptionIcon($icon)
                ->color($color)
                // Gráfico decorativo: verde ascendente si está bien, rojo descendente si falla
                ->chart($estado ? [2, 10, 15, 20, 25, 30, 35] : [35, 20, 10, 5, 2, 1, 0]);
        }

        return $stats;
    }
}
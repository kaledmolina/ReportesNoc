<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use App\Helpers\CanalesHelper;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TvStatsWidget extends BaseWidget
{
    // Orden 5: Aparece después de Servidores
    protected static ?int $sort = 5;
    protected ?string $heading = 'Televisión (Canales)';

    public static function canView(): bool
    {
        return auth()->user()->can('view_widget_tv') || auth()->user()->hasRole('super_admin');
    }

    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $report = Report::latest('created_at')->first();

        if (!$report) {
            return [Stat::make('Televisión', 'Sin datos')->color('gray')];
        }

        $listaCanales = CanalesHelper::getLista();
        $stats = [];

        // 1. ESTADO GRILLA (Canales Activos)
        $total = $report->tv_canales_total ?? 92;
        $activos = $report->tv_canales_activos ?? 0;
        $offlineCount = count($report->tv_canales_offline ?? []);
        
        $stats[] = Stat::make('Grilla de Canales', "{$activos} / {$total}")
            ->description($offlineCount > 0 ? "{$offlineCount} Canales fuera de servicio" : 'Grilla 100% Operativa')
            ->descriptionIcon($offlineCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge')
            ->color($offlineCount > 0 ? 'danger' : 'success')
            ->chart($offlineCount > 0 ? [100, 90, 80, 85, 80] : [90, 95, 98, 100, 100]);

        // 2. LISTADO DE CANALES OFFLINE (Si hay)
        if ($offlineCount > 0) {
            $nombres = collect($report->tv_canales_offline)
                ->map(fn($id) => $listaCanales[$id] ?? $id)
                ->take(3)
                ->implode(', ');
            
            if ($offlineCount > 3) $nombres .= '...';

            $stats[] = Stat::make('Canales Afectados', $offlineCount)
                ->description($nombres)
                ->descriptionIcon('heroicon-m-signal-slash')
                ->color('danger');
        }

        // 3. INTALFLIX (CORREGIDO: Se busca dentro de lista_servidores)
        $intalflixItem = collect($report->lista_servidores ?? [])->firstWhere('nombre', 'Intalflix');
        $intalflixOnline = $intalflixItem['estado'] ?? false; // Si no encuentra el item, asume false

        $stats[] = Stat::make('Intalflix', $intalflixOnline ? 'Online' : 'Caída')
            ->description($intalflixOnline ? 'Servicio estable' : 'Requiere revisión')
            ->descriptionIcon($intalflixOnline ? 'heroicon-m-wifi' : 'heroicon-m-signal-slash')
            ->color($intalflixOnline ? 'success' : 'danger');

        // 4. OBSERVACIÓN TV (Si existe)
        if (!empty($report->tv_observaciones)) {
            $stats[] = Stat::make('Novedad TV', 'Observación')
                ->description($report->tv_observaciones)
                ->descriptionIcon('heroicon-m-chat-bubble-left-ellipsis')
                ->color('warning');
        }

        return $stats;
    }
}
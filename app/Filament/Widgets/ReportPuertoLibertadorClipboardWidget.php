<?php

namespace App\Filament\Widgets;

use App\Models\ReportPuertoLibertador;
use App\Helpers\CanalesHelper;
use Filament\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportPuertoLibertadorClipboardWidget extends Widget
{
    protected static string $view = 'filament.widgets.report-puerto-libertador-clipboard-widget';
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->can('view_widget_clipboard_puerto_libertador') || auth()->user()->hasRole('super_admin');
    }
    
    protected static ?int $sort = 7;

    public function getViewData(): array
    {
        $report = ReportPuertoLibertador::with('incidents')->latest('created_at')->first();

        if (!$report) {
            return [
                'reportText' => 'No hay reportes registrados para Puerto Libertador.',
                'lastUpdate' => null,
            ];
        }

        Carbon::setLocale('es');
        $turnoTexto = match($report->turno) {
            'mañana' => 'primer',
            'tarde' => 'segundo',
            'noche' => 'tercer',
            default => $report->turno
        };

        $text = "Centro de Operaciones de Red – {$turnoTexto} informe Diario Puerto Libertador\n";
        
        $fecha = Carbon::parse($report->fecha)->translatedFormat('d \d\e F');
        $hora = $report->created_at->format('h:i A');
        $text .= "Fecha: {$fecha} - {$hora}\n\n";

        $i = 1;

        // 1. OLT
        $oltStatus = $report->olt_operativa ? 'Operativa ✅' : 'con Fallas ⚠️';
        $text .= "{$i}️⃣OLT Huawei: {$oltStatus}\n";
        $i++;

        // 2. MIKROTIK
        $mkStatus = $report->mikrotik_2116_operativo ? 'Operativo ✅' : 'con Fallas ⚠️';
        $text .= "{$i}️⃣Mikrotik 2116: {$mkStatus}\n";
        $i++;

        // 3. ENLACE
        $enlaceStatus = $report->enlace_dedicado_operativo ? 'Operativo ✅' : 'con Fallas ⚠️';
        $text .= "{$i}️⃣Enlace Dedicado: {$enlaceStatus}\n";
        $i++;

        // 4. TELEVISIÓN
        $tvServerStatus = $report->servidor_tv_operativo ? 'Operativo ✅' : 'con Fallas ⚠️';
        $moduladorStatus = $report->modulador_ip_operativo ? 'Operativo ✅' : 'con Fallas ⚠️';
        
        $text .= "{$i}️⃣Televisión:\n";
        $text .= "   - Servidor TV: {$tvServerStatus}\n";
        $text .= "   - Modulador IP: {$moduladorStatus}\n";

        if (!empty($report->tv_canales_offline)) {
            $listaCanales = CanalesHelper::getLista();
            $text .= "   - Canales Offline:\n";
            foreach($report->tv_canales_offline as $canal) {
                $nombre = $listaCanales[$canal] ?? $canal;
                $text .= "     * {$nombre}\n";
            }
        } else {
            $text .= "   - Grilla de Canales: Completa ✅\n";
        }
        $i++;

        // NOVEDADES
        $text .= "\nNOVEDADES:\n";
        if ($report->observaciones_generales) {
            $text .= "{$report->observaciones_generales}\n\n";
        }

        foreach($report->incidents as $incident) {
            if (!str_contains(strtolower($incident->descripcion ?? ''), 'desde reporte')) {
                continue;
            }
            $icon = match($incident->estado) { 'resuelto' => '✅', 'en_proceso' => '🟠', default => '🔴' };
            $text .= "{$incident->identificador}: {$incident->descripcion} {$icon}\n";
        }

        if ($report->incidents->isEmpty() && empty($report->observaciones_generales)) {
            $text .= "Sin novedades adicionales reportadas.\n";
        }

        // ADJUNTOS
        if (!empty($report->photos)) {
            $text .= "\nAdjuntos:\n";
            foreach ($report->photos as $photo) {
                $fullUrl = asset(Storage::url($photo));
                $text .= "- {$fullUrl}\n";
            }
        }

        return [
            'reportText' => $text,
            'lastUpdate' => $report->created_at->diffForHumans()
        ];
    }
}

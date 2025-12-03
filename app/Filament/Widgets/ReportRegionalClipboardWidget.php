<?php

namespace App\Filament\Widgets;

use App\Models\ReportRegional;
use Filament\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportRegionalClipboardWidget extends Widget
{
    protected static string $view = 'filament.widgets.report-regional-clipboard-widget';
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->can('view_widget_clipboard_regional') || auth()->user()->hasRole('super_admin');
    }

    protected static ?int $sort = 8;

    public function getViewData(): array
    {
        $report = ReportRegional::with('incidents')->latest('created_at')->first();

        if (!$report) {
            return [
                'reportText' => 'No hay reportes regionales registrados.',
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

        $text = "Centro de Operaciones de Red – {$turnoTexto} informe Diario Regionales\n";
        $fecha = Carbon::parse($report->fecha)->translatedFormat('d \d\e F');
        $hora = $report->created_at->format('h:i A');
        $text .= "Fecha: {$fecha} - {$hora}\n\n";

        // VALENCIA
        $text .= "📍 VALENCIA:\n";
        $text .= "- BGP 2116: " . ($report->valencia_bgp_2116_operativo ? '✅' : '⚠️') . "\n";
        $text .= "- OLT Swifts: " . ($report->valencia_olt_swifts_operativa ? '✅' : '⚠️') . "\n";
        $text .= "- Mikrotik 1036: " . ($report->valencia_mikrotik_1036_operativo ? '✅' : '⚠️') . "\n";
        $text .= "- TV Server: " . ($report->valencia_servidor_tv_operativo ? '✅' : '⚠️') . "\n";
        $text .= "- Modulador IP: " . ($report->valencia_modulador_ip_operativo ? '✅' : '⚠️') . "\n";
        $text .= "- Intalflix: " . ($report->valencia_servidor_intalflix_operativo ? '✅' : '⚠️') . "\n";
        $text .= "- Vmix: " . ($report->valencia_servidor_vmix_operativo ? '✅' : '⚠️') . "\n\n";

        // TIERRALTA
        $text .= "📍 TIERRALTA:\n";
        $text .= "- OLT Principal: " . ($report->tierralta_olt_operativa ? '✅' : '⚠️') . "\n";
        $text .= "- OLT 9 de Marzo: " . ($report->tierralta_olt_9_marzo_operativa ? '✅' : '⚠️') . "\n";
        $text .= "- Mikrotik 1036: " . ($report->tierralta_mikrotik_1036_operativo ? '✅' : '⚠️') . "\n";
        $text .= "- Mikrotik Fomento: " . ($report->tierralta_mikrotik_fomento_operativo ? '✅' : '⚠️') . "\n";
        $text .= "- Enlace Urrá: " . ($report->tierralta_enlace_urra_operativo ? '✅' : '⚠️') . "\n";
        $text .= "- Enlace Ancla: " . ($report->tierralta_enlace_ancla_operativo ? '✅' : '⚠️') . "\n\n";

        // SAN PEDRO
        $text .= "📍 SAN PEDRO:\n";
        $text .= "- OLT: " . ($report->san_pedro_olt_operativa ? '✅' : '⚠️') . "\n";
        $text .= "- Mikrotik 1036: " . ($report->san_pedro_mikrotik_1036_operativo ? '✅' : '⚠️') . "\n\n";

        // NOVEDADES
        $text .= "NOVEDADES:\n";
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
        $adjuntos = [];
        if (!empty($report->photos_valencia)) $adjuntos = array_merge($adjuntos, $report->photos_valencia);
        if (!empty($report->photos_tierralta)) $adjuntos = array_merge($adjuntos, $report->photos_tierralta);
        if (!empty($report->photos_san_pedro)) $adjuntos = array_merge($adjuntos, $report->photos_san_pedro);

        if (!empty($adjuntos)) {
            $text .= "\nAdjuntos:\n";
            foreach ($adjuntos as $photo) {
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

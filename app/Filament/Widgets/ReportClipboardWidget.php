<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class ReportClipboardWidget extends Widget
{
    protected static string $view = 'filament.widgets.report-clipboard-widget';
    
    // Lo ponemos arriba del todo para que sea fácil de acceder
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1; 

    public function getViewData(): array
    {
        $report = Report::with('incidents')->latest('created_at')->first();
        
        if (!$report) {
            return ['reportText' => 'No hay reportes registrados.'];
        }

        // --- CONSTRUCCIÓN DEL FORMATO DE WHATSAPP ---
        Carbon::setLocale('es');
        
        // Mapeo de turno a texto ordinal
        $turnoTexto = match($report->turno) {
            'mañana' => 'primer',
            'tarde' => 'segundo',
            'noche' => 'tercer',
            default => $report->turno
        };

        // Cabecera
        $text = "Centro de Operaciones de Red – {$turnoTexto} informe Diario\n";
        $text .= "Fecha: " . Carbon::parse($report->fecha)->translatedFormat('d \d\e F') . "\n";
        $text .= "Ciudad: {$report->ciudad}\n\n";

        // Puntos 1, 2, 3
        $concentradoresIcon = $report->concentradores_ok ? '✅' : '❌ REVISAR';
        $text .= "1️⃣Concentradores en optimo  Funcionamiento  {$concentradoresIcon}\n";
        
        $proveedoresTexto = $report->proveedores_ok ? 'enlazados correctamente' : 'con intermitencia ⚠️';
        $text .= "2️⃣Proveedores {$proveedoresTexto}\n";
        
        $text .= "3️⃣OLT Monteria:{$report->temp_olt_monteria}°\n";
        $text .= "OLT Montería Backup:{$report->temp_olt_backup}°\n";

        // Punto 4: TV
        $text .= "4️⃣Televisión:\n";
        
        if (!empty($report->tv_canales_offline)) {
            $text .= "Canales sin señal:\n";
            foreach($report->tv_canales_offline as $canal) {
                $text .= "{$canal}\n";
            }
            $text .= "\n";
        }

        // Disponibilidad
        $checkTv = ($report->tv_canales_activos == $report->tv_canales_total) ? '✅' : '⚠️';
        $text .= "{$report->tv_canales_activos} Canales funcionando de {$report->tv_canales_total} {$checkTv}\n\n";

        // Intalflix
        $intalflixStatus = $report->intalflix_online ? 'en linea ✅' : 'fuera de servicio ❌';
        $text .= "Intalflix: {$intalflixStatus}\n\n";

        // Novedades
        $text .= "Novedades:\n";
        
        // Si hay resumen general, lo ponemos
        if ($report->observaciones_generales) {
            $text .= "{$report->observaciones_generales}\n\n";
        }

        // Si hay novedades de servidores
        if ($report->novedades_servidores) {
             $text .= "Servidores: {$report->novedades_servidores}\n\n";
        }

        // Listado de incidentes detallados
        foreach($report->incidents as $incident) {
            $estadoIcon = match($incident->estado) {
                'resuelto' => '✅',
                'en_proceso' => '🟠',
                default => '🔴'
            };

            $text .= "{$incident->identificador} ({$incident->barrios}) {$estadoIcon}\n";
            if ($incident->descripcion) {
                $text .= "{$incident->descripcion}\n";
            }
            if ($incident->usuarios_afectados) {
                $text .= "Afectando aprox {$incident->usuarios_afectados} usuarios.\n";
            }
            $text .= "\n";
        }

        if ($report->incidents->isEmpty() && empty($report->observaciones_generales)) {
            $text .= "Sin novedades reportadas.\n";
        }

        return [
            'reportText' => $text,
            'lastUpdate' => $report->created_at->diffForHumans()
        ];
    }
}
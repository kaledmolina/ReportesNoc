<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use App\Helpers\CanalesHelper; 
use Filament\Widgets\Widget;
use Carbon\Carbon;
use App\Models\Incident;
use Illuminate\Support\Facades\Storage;

class ReportClipboardWidget extends Widget
{
    protected static string $view = 'filament.widgets.report-clipboard-widget';
    protected int | string | array $columnSpan = 'full'; 

    public static function canView(): bool
    {
        return auth()->user()->can('view_widget_clipboard') || auth()->user()->hasRole('super_admin');
    }
    protected static ?int $sort = 6; 

    public function getViewData(): array
    {
        $report = Report::with('incidents')->latest('created_at')->first();
        
        if (!$report) {
            return [
                'reportText' => 'No hay reportes registrados.',
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

        $listaCanales = CanalesHelper::getLista();

        $text = "Centro de Operaciones de Red – {$turnoTexto} informe Diario\n";
        
        $fecha = Carbon::parse($report->fecha)->translatedFormat('d \d\e F');
        $hora = $report->created_at->format('h:i A'); 
        $text .= "Fecha: {$fecha} - {$hora}\n";
        
        $text .= "Ciudad: {$report->ciudad}\n\n";

        $i = 1;

        // 1. CONCENTRADORES
        $concentradores = $report->lista_concentradores ?? [];
        $optimos = [];
        $fallas = [];

        if (empty($concentradores)) {
             $concStatus = $report->concentradores_ok ? 'en optimo Funcionamiento ✅' : 'con novedades ⚠️';
             $text .= "{$i}️⃣Concentradores {$concStatus}\n";
             $i++;
        } else {
            foreach ($concentradores as $c) {
                $nombreCorto = str_replace('Concentrador ', '', $c['nombre']);
                if ($c['estado']) { $optimos[] = $nombreCorto; } else { $fallas[] = $c; }
            }

            if (!empty($optimos)) {
                $listaOptimos = implode(',', $optimos);
                $text .= "{$i}️⃣Concentradores {$listaOptimos} en optimo Funcionamiento ✅\n";
                $i++;
            }

            foreach ($fallas as $falla) {
                $detalle = $falla['detalle'] ? $falla['detalle'] : 'presentando fallas';
                $text .= "{$i}️⃣{$falla['nombre']} {$detalle} ⚠️\n";
                $i++;
            }
        }

        // 2. PROVEEDORES
        $proveedores = $report->lista_proveedores ?? [];
        if (empty($proveedores)) {
             $provStatus = $report->proveedores_ok ? 'enlazados correctamente' : 'con fallas';
             $text .= "{$i}️⃣Proveedores {$provStatus}\n";
             $i++;
        } else {
            $todosOk = true;
            foreach($proveedores as $p) {
                if (!$p['estado']) {
                    $todosOk = false;
                    $detalle = $p['detalle'] ? $p['detalle'] : 'no enlazado'; 
                    $text .= "{$i}️⃣{$p['nombre']} {$detalle} ⚠️\n";
                    $i++;
                }
            }
            if ($todosOk) {
                $text .= "{$i}️⃣Proveedores enlazados correctamente ✅\n";
                $i++;
            }
        }

        // 3. SERVIDORES
        $servidores = $report->lista_servidores ?? [];
        $servidoresConFalla = [];
        
        foreach ($servidores as $s) {
            if (!$s['estado']) {
                $servidoresConFalla[] = $s;
            }
        }

        if (empty($servidoresConFalla)) {
            $text .= "{$i}️⃣Servidores y Energía: Operativos ✅\n";
        } else {
            $text .= "{$i}️⃣Servidores con Novedad ⚠️:\n";
            foreach ($servidoresConFalla as $sf) {
                $det = $sf['detalle'] ? $sf['detalle'] : 'Falla reportada';
                $text .= "   - {$sf['nombre']}: {$det}\n";
            }
        }
        $i++;

        // 4. OLTs
        $text .= "{$i}️⃣OLT Monteria: {$report->temp_olt_monteria}°\n";
        $text .= "OLT Montería Backup: {$report->temp_olt_backup}°\n";
        $i++;

        // 5. TELEVISIÓN
        $text .= "{$i}️⃣Televisión:\n";
        
        if (!empty($report->tv_canales_offline)) {
            $text .= "Canales sin señal:\n";
            foreach($report->tv_canales_offline as $canal) {
                $nombreCompleto = $listaCanales[$canal] ?? $canal;
                $text .= "- {$nombreCompleto}\n"; 
            }
            $text .= "\n";
        }

        if ($report->tv_observaciones) { 
            $text .= "Nota TV: {$report->tv_observaciones}\n\n"; 
        }

        $checkTv = ($report->tv_canales_activos == $report->tv_canales_total) ? '✅' : '⚠️';
        $text .= "{$report->tv_canales_activos} Canales funcionando de {$report->tv_canales_total} {$checkTv}\n\n";

        // SE ELIMINÓ EL BLOQUE INTALFLIX (Ya incluido en Servidores)

        // NOVEDADES
        $text .= "NOVEDADES:\n";
        if ($report->observaciones_generales) { $text .= "{$report->observaciones_generales}\n\n"; }
        if ($report->novedades_servidores) { $text .= "Infraestructura: {$report->novedades_servidores}\n\n"; }

        foreach($report->incidents as $incident) {
            $icon = match($incident->estado) { 'resuelto' => '✅', 'en_proceso' => '🟠', default => '🔴' };

            if ($incident->tipo_falla === 'falla_olt') {
                $titulo = "Falla OLT {$incident->olt_nombre}";
                $detalleTecnico = "";
                if (!empty($incident->olt_afectacion)) {
                    $partes = [];
                    foreach($incident->olt_afectacion as $afec) {
                        $puertos = implode(',', $afec['puertos']);
                        $partes[] = "Tarjeta {$afec['tarjeta']} (Ptos: {$puertos})";
                    }
                    $detalleTecnico = implode(' | ', $partes);
                }
                $text .= "{$titulo} - {$detalleTecnico} {$icon}\n";

            } elseif ($incident->tipo_falla === 'falla_tv') {
                $canalesAfectados = collect($incident->tv_canales_afectados ?? [])
                    ->map(fn($c) => $listaCanales[$c] ?? $c)
                    ->implode(', ');
                
                $text .= "Falla Servidor TV (Afectando: {$canalesAfectados}) {$icon}\n";

            } else {
                $text .= "{$incident->identificador} {$icon}\n";
            }

            if ($incident->barrios) { $text .= "Sector: {$incident->barrios}\n"; }
            if ($incident->descripcion) { $text .= "Detalle: {$incident->descripcion}\n"; }
            $text .= "\n";
        }

        if ($report->incidents->isEmpty() && empty($report->observaciones_generales) && empty($report->novedades_servidores)) {
            $text .= "Sin novedades adicionales reportadas.\n";
        } else {
            // Resumen de Incidentes
            $pendientes = $report->incidents->where('estado', 'pendiente')->count();
            $enProceso = $report->incidents->where('estado', 'en_proceso')->count();
            $resueltos = $report->incidents->where('estado', 'resuelto')->count();

            $resumen = [];
            if ($pendientes > 0) $resumen[] = "{$pendientes} pendientes";
            if ($enProceso > 0) $resumen[] = "{$enProceso} en proceso";
            if ($resueltos > 0) $resumen[] = "{$resueltos} resueltos";

            if (!empty($resumen)) {
                $text .= "\nResumen: " . implode(', ', $resumen);
            }
        }

        // Tickets generados en este reporte
        $ticketsHoy = $report->incidents->count();
        $text .= "\nTickets generados en este reporte: {$ticketsHoy}\n";

        // Adjuntos (Fotos)
        if (!empty($report->photos)) {
            $text .= "\nAdjuntos:\n";
            foreach ($report->photos as $photo) {
                $url = Storage::url($photo);
                // Si usas un driver como S3 o similar que genere URLs completas, esto está bien.
                // Si es local, Storage::url devuelve una ruta relativa (/storage/...), 
                // así que podrías querer anteponer el APP_URL si es necesario para compartir externamente.
                // Para este caso asumiremos que Storage::url es suficiente o que el cliente lo maneja.
                // Para asegurar URL completa en local/producción si es 'public':
                $fullUrl = asset($url); 
                $text .= "- {$fullUrl}\n";
            }
        }

        return [
            'reportText' => $text,
            'lastUpdate' => $report->created_at->diffForHumans()
        ];
    }
}
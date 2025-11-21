<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\Report;
use App\Models\Incident;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected function afterCreate(): void
    {
        $nuevoReporte = $this->record;

        // 1. CONTINUIDAD: Mover pendientes del turno anterior
        // Buscamos el último reporte creado antes de este
        $reporteAnterior = Report::where('id', '!=', $nuevoReporte->id)
            ->latest('created_at')
            ->first();

        if ($reporteAnterior) {
            // Buscamos incidentes no resueltos
            $pendientes = $reporteAnterior->incidents()
                ->whereIn('estado', ['pendiente', 'en_proceso'])
                ->get();

            $contadorMovidos = 0;

            foreach ($pendientes as $incidente) {
                // --- EL CAMBIO CLAVE ---
                // En lugar de replicate(), solo actualizamos el report_id.
                // Esto "mueve" el registro físico en la base de datos.
                // Resultado: Se borra del anterior, aparece en este, y la fecha 'created_at' queda intacta.
                
                $incidente->report_id = $nuevoReporte->id;
                $incidente->save();
                
                $contadorMovidos++;
            }

            if ($contadorMovidos > 0) {
                Notification::make()
                    ->title('Continuidad de Casos')
                    ->body("Se han trasladado {$contadorMovidos} casos pendientes manteniendo su antigüedad original.")
                    ->success()
                    ->send();
            }
        }

        // 2. Procesar tickets manuales en cola (OLT) - Esta lógica se mantiene igual
        $this->procesarManuales($nuevoReporte, 'Main', $nuevoReporte->olt_monteria_detalle);
        $this->procesarManuales($nuevoReporte, 'Backup', $nuevoReporte->olt_backup_detalle);
    }

    private function procesarManuales(Report $reporte, string $nombreOlt, ?array $detalles)
    {
        if (empty($detalles)) return;

        foreach ($detalles as $item) {
            // Si se marcó el botón "Generar Ticket" (vinculado) y está en falla
            if (isset($item['incidente_vinculado']) && $item['incidente_vinculado'] === true && $item['estado'] === false) {
                $tarjeta = $item['tarjeta'] ?? null;
                $puertos = $item['puertos'] ?? [];
                
                if ($tarjeta) {
                    // Validar duplicado final antes de crear
                    $existe = $reporte->incidents()
                        ->where('tipo_falla', 'falla_olt')
                        ->where('olt_nombre', $nombreOlt)
                        ->get()
                        ->contains(fn ($inc) => collect($inc->olt_afectacion ?? [])->contains('tarjeta', $tarjeta));
                    
                    if (!$existe) {
                        Incident::create([
                            'report_id' => $reporte->id,
                            'tipo_falla' => 'falla_olt',
                            'olt_nombre' => $nombreOlt,
                            'olt_afectacion' => [['tarjeta' => $tarjeta, 'puertos' => $puertos]],
                            'barrios' => 'N/A',
                            'estado' => 'pendiente',
                            'descripcion' => "Ticket manual generado desde reporte.",
                            'identificador' => "OLT {$nombreOlt} - T{$tarjeta}"
                        ]);
                    }
                }
            }
        }
    }
}
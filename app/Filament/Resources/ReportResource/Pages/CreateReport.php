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

    protected function beforeCreate(): void
    {
        $data = $this->data;

        // 1. Validar OLT Main
        if (!empty($data['olt_monteria_detalle'])) {
            foreach ($data['olt_monteria_detalle'] as $item) {
                // Si está en falla (estado false) y NO tiene incidente vinculado
                if (isset($item['estado']) && $item['estado'] === false && empty($item['incidente_vinculado'])) {
                    Notification::make()
                        ->title('Ticket Requerido')
                        ->body("Debes generar el ticket para la falla en OLT Main - Tarjeta {$item['tarjeta']}")
                        ->danger()
                        ->persistent()
                        ->send();
                    $this->halt();
                }
            }
        }

        // 2. Validar OLT Backup
        if (!empty($data['olt_backup_detalle'])) {
            foreach ($data['olt_backup_detalle'] as $item) {
                if (isset($item['estado']) && $item['estado'] === false && empty($item['incidente_vinculado'])) {
                    Notification::make()
                        ->title('Ticket Requerido')
                        ->body("Debes generar el ticket para la falla en OLT Backup - Tarjeta {$item['tarjeta']}")
                        ->danger()
                        ->persistent()
                        ->send();
                    $this->halt();
                }
            }
        }

        // 3. Validar TV
        if (!empty($data['tv_canales_offline'])) {
            $ticketExistente = $data['tv_ticket_existente'] ?? false;
            $ticketEnCola = $data['tv_ticket_en_cola'] ?? false;

            if (!$ticketExistente && !$ticketEnCola) {
                Notification::make()
                    ->title('Ticket Requerido')
                    ->body("Debes generar el ticket para los canales fuera de servicio")
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }
        }
    }

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

        // 3. Procesar ticket manual en cola (TV)
        if (!empty($this->data['tv_ticket_en_cola']) && !empty($this->data['tv_canales_offline'])) {
            $canales = $this->data['tv_canales_offline'];
            $responsibleId = $this->data['tv_responsible_id'] ?? null;
            
            // Validar duplicado GLOBAL antes de crear
            $existe = Incident::where('tipo_falla', 'falla_tv')
                ->whereIn('estado', ['pendiente', 'en_proceso'])
                ->get()
                ->contains(fn ($inc) => count(array_intersect($canales, $inc->tv_canales_afectados ?? [])) > 0);

            if (!$existe) {
                $incident = Incident::create([
                    'report_id' => $nuevoReporte->id,
                    'tipo_falla' => 'falla_tv',
                    'tv_canales_afectados' => $canales,
                    'barrios' => 'General',
                    'estado' => 'pendiente',
                    'descripcion' => 'Falla TV Manual generada desde reporte.',
                    'identificador' => 'Falla TV'
                ]);
                
                if ($responsibleId) {
                    $incident->responsibles()->attach($responsibleId, [
                        'status' => 'pending',
                        'assigned_by' => auth()->id(),
                        'assigned_at' => now(),
                    ]);
                }
                
                Notification::make()
                    ->title('Ticket TV Creado')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Ticket TV Duplicado')
                    ->body('No se creó el ticket porque ya existe uno activo para estos canales.')
                    ->warning()
                    ->send();
            }
        }
    }

    private function procesarManuales(Report $reporte, string $nombreOlt, ?array $detalles)
    {
        if (empty($detalles)) return;

        foreach ($detalles as $item) {
            // Si se marcó el botón "Generar Ticket" (vinculado) y está en falla
            if (isset($item['incidente_vinculado']) && $item['incidente_vinculado'] === true && $item['estado'] === false) {
                $tarjeta = $item['tarjeta'] ?? null;
                $puertos = $item['puertos'] ?? [];
                $responsibleId = $item['responsible_id'] ?? null;
                
                if ($tarjeta) {
                    // Validar duplicado GLOBAL antes de crear
                    // Buscamos si existe algún incidente activo (pendiente/en_proceso)
                    // que tenga la misma OLT, misma tarjeta y al menos un puerto coincidente.
                    
                    $existe = Incident::where('tipo_falla', 'falla_olt')
                        ->where('olt_nombre', $nombreOlt)
                        ->whereIn('estado', ['pendiente', 'en_proceso'])
                        ->get()
                        ->contains(function ($inc) use ($tarjeta, $puertos) {
                            $afectaciones = $inc->olt_afectacion ?? [];
                            foreach ($afectaciones as $afectacion) {
                                if (isset($afectacion['tarjeta']) && $afectacion['tarjeta'] == $tarjeta) {
                                    // Verificar intersección de puertos
                                    $puertosExistentes = $afectacion['puertos'] ?? [];
                                    $puertosCoincidentes = array_intersect($puertos, $puertosExistentes);
                                    
                                    if (!empty($puertosCoincidentes)) {
                                        return true; // Encontró duplicado
                                    }
                                }
                            }
                            return false;
                        });
                    
                    if (!$existe) {
                        $incident = Incident::create([
                            'report_id' => $reporte->id,
                            'tipo_falla' => 'falla_olt',
                            'olt_nombre' => $nombreOlt,
                            'olt_afectacion' => [['tarjeta' => $tarjeta, 'puertos' => $puertos]],
                            'barrios' => 'N/A',
                            'estado' => 'pendiente',
                            'descripcion' => "Ticket manual generado desde reporte.",
                            'identificador' => "OLT {$nombreOlt} - T{$tarjeta}"
                        ]);

                        if ($responsibleId) {
                            $incident->responsibles()->attach($responsibleId, [
                                'status' => 'pending',
                                'assigned_by' => auth()->id(),
                                'assigned_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
<?php

namespace App\Filament\Resources\IncidentResource\Pages;

use App\Filament\Resources\IncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->data;
        
        // 1. Validar Duplicados de OLT (Tarjeta y Puerto)
        if ($data['tipo_falla'] === 'falla_olt') {
            $oltNombre = $data['olt_nombre'];
            $nuevasAfectaciones = $data['olt_afectacion'] ?? [];

            foreach ($nuevasAfectaciones as $afectacion) {
                $tarjeta = $afectacion['tarjeta'];
                $puertos = $afectacion['puertos'] ?? [];

                // Buscar incidentes activos (pendientes o en proceso) de la misma OLT
                $incidentesActivos = \App\Models\Incident::where('tipo_falla', 'falla_olt')
                    ->where('olt_nombre', $oltNombre)
                    ->whereIn('estado', ['pendiente', 'en_proceso'])
                    ->get();

                foreach ($incidentesActivos as $incidente) {
                    $afectacionesExistentes = $incidente->olt_afectacion ?? [];
                    
                    foreach ($afectacionesExistentes as $existente) {
                        // Si es la misma tarjeta
                        if (isset($existente['tarjeta']) && $existente['tarjeta'] == $tarjeta) {
                            // Verificar intersección de puertos
                            $puertosExistentes = $existente['puertos'] ?? [];
                            $puertosCoincidentes = array_intersect($puertos, $puertosExistentes);

                            if (!empty($puertosCoincidentes)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Duplicado Detectado')
                                    ->body("Ya existe un ticket activo (#{$incidente->ticket_number}) para la OLT {$oltNombre}, Tarjeta {$tarjeta}, Puertos: " . implode(', ', $puertosCoincidentes))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                
                                $this->halt(); // Detener la creación
                            }
                        }
                    }
                }
            }
        }

        // 2. Validar Duplicados de TV (Canales)
        if ($data['tipo_falla'] === 'falla_tv') {
            $canalesNuevos = $data['tv_canales_afectados'] ?? [];

            if (!empty($canalesNuevos)) {
                // Buscar incidentes de TV activos
                $incidentesTV = \App\Models\Incident::where('tipo_falla', 'falla_tv')
                    ->whereIn('estado', ['pendiente', 'en_proceso'])
                    ->get();

                foreach ($incidentesTV as $incidente) {
                    $canalesExistentes = $incidente->tv_canales_afectados ?? [];
                    $canalesCoincidentes = array_intersect($canalesNuevos, $canalesExistentes);

                    if (!empty($canalesCoincidentes)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Duplicado Detectado')
                            ->body("Ya existe un ticket activo (#{$incidente->ticket_number}) reportando los canales: " . implode(', ', $canalesCoincidentes))
                            ->danger()
                            ->persistent()
                            ->send();
                        
                        $this->halt(); // Detener la creación
                    }
                }
            }
        }
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        
        // Cargar la relación para asegurar que tenemos los usuarios asignados
        $record->load('responsibles');

        // Actualizar datos pivote para los responsables asignados
        $record->responsibles()->updateExistingPivot($record->responsibles->pluck('id'), [
            'status' => 'pending',
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        // Enviar notificaciones
        foreach ($record->responsibles as $user) {
            \Filament\Notifications\Notification::make()
                ->title('Nuevo Ticket Asignado')
                ->body("Se te ha asignado el ticket #{$record->ticket_number}")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('ver')
                        ->button()
                        ->url(IncidentResource::getUrl('edit', ['record' => $record])),
                ])
                ->sendToDatabase($user);
        }
    }
}

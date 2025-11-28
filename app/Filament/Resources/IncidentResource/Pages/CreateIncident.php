<?php

namespace App\Filament\Resources\IncidentResource\Pages;

use App\Filament\Resources\IncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

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

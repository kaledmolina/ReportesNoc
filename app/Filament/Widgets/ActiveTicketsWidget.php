<?php

namespace App\Filament\Widgets;

use App\Models\Incident;
use App\Filament\Resources\IncidentResource; // Importamos el Recurso para usar su formulario
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;

class ActiveTicketsWidget extends BaseWidget
{
    protected static ?int $sort = 3; 
    protected int | string | array $columnSpan = 'full'; 
    protected static ?string $heading = '🚨 Seguimiento de Incidentes Activos';

    public function table(Table $table): Table
    {
        return $table
            // Traemos todo lo que NO esté resuelto
            ->query(
                Incident::query()->where('estado', '!=', 'resuelto')
            )
            // Refrescamos el widget cada 10 segundos para ver cambios de otros usuarios
            ->poll('10s') 
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reportado')
                    ->since()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tipo_falla')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'falla_olt' => 'OLT',
                        'falla_tv' => 'TV',
                        'fibra' => 'Fibra',
                        'energia' => 'Energía',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'falla_olt', 'fibra' => 'danger',
                        'energia' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('identificador_visual')
                    ->label('Equipo / Afectación')
                    ->weight('bold')
                    ->state(function (Incident $record) {
                        if ($record->tipo_falla === 'falla_olt') return "OLT {$record->olt_nombre}";
                        return $record->identificador;
                    })
                    ->description(fn (Incident $record) => $record->barrios),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'danger',
                        'en_proceso' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => '🔴 Pendiente',
                        'en_proceso' => '🟠 En Revisión',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('report.turno')
                    ->label('Origen')
                    ->formatStateUsing(fn ($state, $record) => "Reporte {$record->report->fecha->format('d/m')} ({$state})")
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto por defecto para limpiar la vista
            ])
            ->actions([
                // ACCIÓN 1: DE PENDIENTE A EN PROCESO
                Tables\Actions\Action::make('iniciar_proceso')
                    ->label('Atender')
                    ->icon('heroicon-m-play') // Ícono de "Play"
                    ->color('warning') // Naranja
                    ->button() // Estilo botón para que destaque
                    ->visible(fn (Incident $record) => $record->estado === 'pendiente')
                    ->action(function (Incident $record) {
                        $record->update(['estado' => 'en_proceso']);
                        Notification::make()->title('Caso en seguimiento')->warning()->send();
                    }),

                // ACCIÓN 2: DE EN PROCESO A RESUELTO
                Tables\Actions\Action::make('finalizar_caso')
                    ->label('Finalizar')
                    ->icon('heroicon-m-check-badge') // Ícono de Check
                    ->color('success') // Verde
                    ->button()
                    ->visible(fn (Incident $record) => $record->estado === 'en_proceso')
                    ->requiresConfirmation()
                    ->modalHeading('¿Cerrar Incidente?')
                    ->modalDescription('El incidente desaparecerá de esta lista y quedará marcado como resuelto.')
                    ->modalSubmitActionLabel('Sí, solucionar')
                    ->action(function (Incident $record) {
                        $record->update(['estado' => 'resuelto']);
                        Notification::make()->title('Incidente Solucionado')->success()->send();
                    }),
                
                // ACCIÓN 3: VER DETALLE (OJO)
                // Usamos el formulario del recurso IncidentResource para mostrar todos los campos
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalle')
                    ->icon('heroicon-m-eye')
                    ->iconButton()
                    ->color('gray')
                    ->modalHeading('Detalle del Incidente')
                    ->form(fn ($form) => IncidentResource::form($form)),
            ]);
    }
}
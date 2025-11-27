<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Filament\Resources\IncidentResource\RelationManagers;
use App\Models\Incident;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignedIncidentResource extends Resource
{
    protected static ?string $model = Incident::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Tickets';
    protected static ?string $navigationLabel = 'Tickets Asignados';
    protected static ?string $slug = 'assigned-incidents';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        // Reusing the same form schema from IncidentResource would be ideal, 
        // but for now we can just use the View page which uses the same resource.
        // Or we can duplicate the schema if needed, but since it's read-only mostly for this view...
        // Let's call IncidentResource::form($form) if it was static and reusable, 
        // but it's easier to just define what's needed or reuse the class.
        return IncidentResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket #')
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('identificador_visual')
                    ->label('Incidente')
                    ->state(function (Incident $record) {
                        return $record->identificador;
                    })
                    ->description(fn (Incident $record) => $record->tipo_falla),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'resuelto' => 'success', 'pendiente' => 'danger', 'en_proceso' => 'warning', default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    // ACCIÓN: ATENDER
                    Tables\Actions\Action::make('atender')
                        ->label('Atender')
                        ->icon('heroicon-o-play')
                        ->color('warning')
                        ->visible(fn (Incident $record) => $record->estado === 'pendiente')
                        ->requiresConfirmation()
                        ->action(function (Incident $record) {
                            $record->update(['estado' => 'en_proceso']);
                            
                            // Update pivot status if needed
                            $record->responsibles()->updateExistingPivot(auth()->id(), [
                                'status' => 'accepted',
                                'accepted_at' => now(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Ticket en proceso')
                                ->success()
                                ->send();
                        }),

                    // ACCIÓN: ESCALAR
                    Tables\Actions\Action::make('escalar')
                        ->label('Escalar (Reasignar)')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('danger')
                        ->visible(fn (Incident $record) => $record->estado === 'pendiente') // Solo si está pendiente (no resuelto ni en proceso)
                        ->form([
                            Forms\Components\Select::make('nuevo_responsable')
                                ->label('Escalar a:')
                                ->options(\App\Models\User::where('id', '!=', auth()->id())->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            Forms\Components\Textarea::make('motivo')
                                ->label('Motivo del escalamiento')
                                ->required(),
                        ])
                        ->action(function (Incident $record, array $data) {
                            // 1. Update current user status to 'escalated'
                            $record->responsibles()->updateExistingPivot(auth()->id(), [
                                'status' => 'escalated',
                                'notes' => "Escalado por " . auth()->user()->name . ": " . $data['motivo'],
                                'escalated_at' => now(), // Asegúrate de tener esta columna en la migración o usa updated_at
                            ]);

                            // 2. Add new user
                            $record->responsibles()->attach($data['nuevo_responsable'], [
                                'status' => 'pending',
                                'assigned_by' => auth()->id(),
                                'assigned_at' => now(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Ticket Escalado')
                                ->body('El ticket ha sido reasignado correctamente.')
                                ->success()
                                ->send();
                        }),

                    // ACCIÓN: RESOLVER
                    Tables\Actions\Action::make('resolver')
                        ->label('Resolver')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Incident $record) => $record->estado === 'en_proceso')
                        ->form([
                            Forms\Components\FileUpload::make('photos_resolution')
                                ->label('Evidencias de Solución')
                                ->multiple()
                                ->image()
                                ->imageEditor()
                                ->directory('incident-resolution-photos')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('notas_resolucion')
                                ->label('Notas de Resolución')
                                ->required()
                                ->placeholder('Describe cómo se solucionó el incidente...'),
                        ])
                        ->action(function (Incident $record, array $data) {
                            $record->update([
                                'estado' => 'resuelto',
                                'photos_resolution' => $data['photos_resolution'],
                                // Podríamos guardar las notas en la descripción o en un campo nuevo, 
                                // por ahora lo concatenamos a la descripción o usamos un campo de notas si existiera.
                                // Vamos a concatenarlo a la descripción para no crear más campos por ahora,
                                // o mejor aún, actualizar el pivot del usuario.
                            ]);

                            // Actualizar pivot del usuario
                            $record->responsibles()->updateExistingPivot(auth()->id(), [
                                // 'status' => 'resolved', // No cambiamos el status porque el enum no lo permite
                                'notes' => "Resuelto: " . $data['notas_resolucion'],
                                'resolved_at' => now(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Ticket Resuelto')
                                ->body('El incidente ha sido marcado como resuelto.')
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ResponsiblesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Show tickets where the CURRENT user is a responsible
        return parent::getEloquentQuery()->whereHas('responsibles', function ($q) {
            $q->where('user_id', auth()->id());
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\AssignedIncidentResource\Pages\ListAssignedIncidents::route('/'),
            'view' => \App\Filament\Resources\AssignedIncidentResource\Pages\ViewAssignedIncident::route('/{record}'),
        ];
    }
}

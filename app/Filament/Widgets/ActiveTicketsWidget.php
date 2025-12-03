<?php

namespace App\Filament\Widgets;

use App\Models\Incident;
use App\Filament\Resources\IncidentResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

class ActiveTicketsWidget extends BaseWidget
{
    protected static ?int $sort = 3; 
    protected int | string | array $columnSpan = 'full'; 
    protected static ?string $heading = '🚨 Mis Tickets Asignados';

    public static function canView(): bool
    {
        return auth()->user()->can('view_widget_active_tickets') || auth()->user()->hasRole('super_admin');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Incident::query()
                    ->whereHas('responsibles', function (Builder $query) {
                        $query->where('user_id', auth()->id());
                    })
                    ->where('estado', '!=', 'resuelto')
            )
            ->poll('10s') 
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reportado')
                    ->since()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Creado Por')
                    ->icon('heroicon-m-user')
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
                        if ($record->tipo_falla === 'falla_olt') {
                            return "OLT {$record->olt_nombre}";
                        }
                        return $record->identificador;
                    }),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'danger',
                        'en_proceso' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // ACCIÓN: VER DETALLE
                    Tables\Actions\ViewAction::make()
                        ->label('Ver')
                        ->icon('heroicon-m-eye')
                        ->color('gray')
                        ->modalHeading('Detalle del Incidente')
                        ->form(fn ($form) => IncidentResource::form($form)),

                    // ACCIÓN: GESTIONAR (CHAT + ESTADOS)
                    Tables\Actions\Action::make('gestionar')
                        ->label('Gestionar')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->modalHeading(fn (Incident $record) => "Gestionar Ticket #{$record->ticket_number}")
                        ->modalContent(fn (Incident $record) => view('filament.pages.actions.ticket-chat-modal', ['record' => $record]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false),

                    // ACCIÓN: ACEPTAR
                    Tables\Actions\Action::make('aceptar')
                        ->label('Aceptar')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(function (Incident $record) {
                            if ($record->estado === 'resuelto') return false;
                            
                            $pivot = $record->responsibles()
                                ->where('user_id', auth()->id())
                                ->first()
                                ?->pivot;
                                
                            return $pivot && $pivot->status === 'pending';
                        })
                        ->requiresConfirmation()
                        ->action(function (Incident $record) {
                            $record->responsibles()->updateExistingPivot(auth()->id(), [
                                'status' => 'accepted',
                                'accepted_at' => now(),
                            ]);
                            Notification::make()
                                ->title('Ticket Aceptado')
                                ->body('Ahora puedes atender o escalar el ticket.')
                                ->success()
                                ->send();
                        }),

                    // ACCIÓN: RECHAZAR
                    Tables\Actions\Action::make('reject_ticket')
                        ->label('Rechazar')
                        ->icon('heroicon-m-x-mark')
                        ->color('danger')
                        ->visible(function (Incident $record) {
                            $pivot = $record->responsibles()
                                ->where('user_id', auth()->id())
                                ->first()
                                ?->pivot;
                            
                            return $pivot && $pivot->status === 'pending';
                        })
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('Motivo del rechazo')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Incident $record, array $data) {
                            $record->responsibles()->updateExistingPivot(auth()->id(), [
                                'status' => 'rejected',
                                'rejected_at' => now(),
                                'notes' => $data['notes'],
                            ]);

                            // Borrar notificación
                            auth()->user()->notifications()
                                ->where('data', 'like', '%ticket_number":"' . $record->ticket_number . '"%')
                                ->orWhere('data', 'like', '%tickets/' . $record->id . '/edit%')
                                ->delete();

                            Notification::make()->title('Ticket Rechazado')->warning()->send();
                        }),

                    // ACCIÓN: ATENDER
                    Tables\Actions\Action::make('atender')
                        ->label('Atender')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->visible(function (Incident $record) {
                            if ($record->estado !== 'pendiente') return false;

                            $pivot = $record->responsibles()
                                ->where('user_id', auth()->id())
                                ->first()
                                ?->pivot;

                            return $pivot && $pivot->status === 'accepted';
                        })
                        ->requiresConfirmation()
                        ->action(function (Incident $record) {
                            $record->update(['estado' => 'en_proceso']);
                            Notification::make()
                                ->title('Atendiendo Ticket')
                                ->body('El ticket ahora está en proceso.')
                                ->success()
                                ->send();
                        }),

                    // ACCIÓN: ESCALAR
                    Tables\Actions\Action::make('escalar')
                        ->label('Escalar')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('danger')
                        ->visible(function (Incident $record) {
                            if ($record->estado !== 'pendiente') return false;

                            $pivot = $record->responsibles()
                                ->where('user_id', auth()->id())
                                ->first()
                                ?->pivot;

                            return $pivot && $pivot->status === 'accepted';
                        })
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
                            $record->responsibles()->updateExistingPivot(auth()->id(), [
                                'status' => 'escalated',
                                'notes' => "Escalado por " . auth()->user()->name . ": " . $data['motivo'],
                                'escalated_at' => now(),
                            ]);

                            $record->responsibles()->attach($data['nuevo_responsable'], [
                                'status' => 'pending',
                                'assigned_by' => auth()->id(),
                                'assigned_at' => now(),
                            ]);

                            // Borrar notificación del usuario actual
                            auth()->user()->notifications()
                                ->where('data', 'like', '%ticket_number":"' . $record->ticket_number . '"%')
                                ->orWhere('data', 'like', '%tickets/' . $record->id . '/edit%')
                                ->delete();

                            // Crear notificación para el nuevo responsable
                            $newResponsible = \App\Models\User::find($data['nuevo_responsable']);
                            if ($newResponsible) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Nuevo Ticket Asignado')
                                    ->body("Se te ha asignado el ticket #{$record->ticket_number}")
                                    ->warning()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('ver')
                                            ->button()
                                            ->url(\Filament\Facades\Filament::getPanel('admin')->getUrl()),
                                    ])
                                    ->sendToDatabase($newResponsible);
                            }

                            Notification::make()
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
                        ->visible(function (Incident $record) {
                            if ($record->estado !== 'en_proceso') return false;

                            $pivot = $record->responsibles()
                                ->where('user_id', auth()->id())
                                ->first()
                                ?->pivot;

                            return $pivot && $pivot->status === 'accepted';
                        })
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
                            ]);

                            $record->responsibles()->updateExistingPivot(auth()->id(), [
                                'notes' => "Resuelto: " . $data['notas_resolucion'],
                                'resolved_at' => now(),
                            ]);

                            // Borrar notificación
                            auth()->user()->notifications()
                                ->where('data', 'like', '%ticket_number":"' . $record->ticket_number . '"%')
                                ->orWhere('data', 'like', '%tickets/' . $record->id . '/edit%')
                                ->delete();

                            Notification::make()
                                ->title('Ticket Resuelto')
                                ->body('El incidente ha sido marcado como resuelto.')
                                ->success()
                                ->send();
                        }),
                ])
            ]);
    }
}
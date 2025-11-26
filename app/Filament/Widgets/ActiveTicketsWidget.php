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
                // ACCIÓN: VER DETALLE (Siempre visible)
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->iconButton()
                    ->color('gray')
                    ->modalHeading('Detalle del Incidente')
                    ->form(fn ($form) => IncidentResource::form($form)),

                // ACCIÓN: ACEPTAR TICKET
                Tables\Actions\Action::make('accept_ticket')
                    ->label('Aceptar')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->button()
                    ->visible(function (Incident $record) {
                        $myPivot = $record->responsibles()
                            ->where('user_id', auth()->id())
                            ->first()
                            ?->pivot;
                        
                        return $myPivot && $myPivot->status === 'pending';
                    })
                    ->action(function (Incident $record) {
                        $record->responsibles()->updateExistingPivot(auth()->id(), [
                            'status' => 'accepted',
                            'accepted_at' => now(),
                        ]);
                        Notification::make()->title('Ticket Aceptado')->success()->send();
                    }),

                // ACCIÓN: RECHAZAR TICKET
                Tables\Actions\Action::make('reject_ticket')
                    ->label('Rechazar')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->button()
                    ->visible(function (Incident $record) {
                        $myPivot = $record->responsibles()
                            ->where('user_id', auth()->id())
                            ->first()
                            ?->pivot;
                        
                        return $myPivot && $myPivot->status === 'pending';
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
                        Notification::make()->title('Ticket Rechazado')->warning()->send();
                    }),

                // ACCIÓN: ATENDER (Solo si ya acepté Y soy el último responsable)
                Tables\Actions\Action::make('iniciar_proceso')
                    ->label('Atender')
                    ->icon('heroicon-m-play')
                    ->color('warning')
                    ->button()
                    ->visible(function (Incident $record) {
                        // 1. Debe estar aceptado por mí
                        $myPivot = $record->responsibles()
                            ->where('user_id', auth()->id())
                            ->first()
                            ?->pivot;
                        
                        if (!$myPivot || $myPivot->status !== 'accepted') {
                            return false;
                        }

                        // 2. Debo ser el ÚLTIMO asignado (responsable actual)
                        $lastResponsible = $record->responsibles()
                            ->orderByPivot('created_at', 'desc')
                            ->first();
                        
                        return $lastResponsible && $lastResponsible->id === auth()->id() && $record->estado === 'pendiente';
                    })
                    ->action(function (Incident $record) {
                        $record->update(['estado' => 'en_proceso']);
                        Notification::make()->title('Caso en seguimiento')->warning()->send();
                    }),

                // ACCIÓN: FINALIZAR
                Tables\Actions\Action::make('finalizar_caso')
                    ->label('Finalizar')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->button()
                    ->visible(fn (Incident $record) => $record->estado === 'en_proceso')
                    ->requiresConfirmation()
                    ->action(function (Incident $record) {
                        $record->update(['estado' => 'resuelto']);
                        Notification::make()->title('Incidente Solucionado')->success()->send();
                    }),
            ]);
    }
}
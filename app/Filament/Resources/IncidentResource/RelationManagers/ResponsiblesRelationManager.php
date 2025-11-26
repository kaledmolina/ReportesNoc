<?php

namespace App\Filament\Resources\IncidentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ResponsiblesRelationManager extends RelationManager
{
    protected static string $relationship = 'responsibles';
    protected static ?string $title = 'Historial de Asignaciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuario')
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('pivot.status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'accepted' => 'Aceptado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('pivot.assigned_at')
                    ->label('Asignado')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('pivot.accepted_at')
                    ->label('Aceptado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('pivot.rejected_at')
                    ->label('Rechazado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('pivot.notes')
                    ->label('Notas / Motivo')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->pivot->notes),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create action, assignments are done via the main resource action
            ])
            ->actions([
                // Read-only mostly
            ])
            ->bulkActions([
                //
            ]);
    }
}

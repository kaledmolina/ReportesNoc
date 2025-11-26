<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $modelLabel = 'Área';
    protected static ?string $pluralModelLabel = 'Áreas (Roles)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Área')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                
                Forms\Components\Section::make('Permisos de Incidentes')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions_incidents')
                            ->label('Acciones sobre Incidentes')
                            ->relationship('permissions', 'name', fn ($query) => $query->where('name', 'like', '%_incident'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => str_replace('_incident', '', $record->name))
                            ->bulkToggleable()
                            ->columns(3),
                    ])->collapsible(),

                Forms\Components\Section::make('Permisos de Reportes (Montería)')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions_reports')
                            ->label('Acciones sobre Reportes')
                            ->relationship('permissions', 'name', fn ($query) => $query->where('name', 'like', '%_report'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => str_replace('_report', '', $record->name))
                            ->bulkToggleable()
                            ->columns(3),
                    ])->collapsible(),

                Forms\Components\Section::make('Permisos de Reportes (Puerto Libertador)')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions_pl')
                            ->label('Acciones sobre Reportes PL')
                            ->relationship('permissions', 'name', fn ($query) => $query->where('name', 'like', '%_report_puerto_libertador'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => str_replace('_report_puerto_libertador', '', $record->name))
                            ->bulkToggleable()
                            ->columns(3),
                    ])->collapsible(),

                Forms\Components\Section::make('Permisos de Reportes (Regional)')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions_regional')
                            ->label('Acciones sobre Reportes Regionales')
                            ->relationship('permissions', 'name', fn ($query) => $query->where('name', 'like', '%_report_regional'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => str_replace('_report_regional', '', $record->name))
                            ->bulkToggleable()
                            ->columns(3),
                    ])->collapsible(),

                Forms\Components\Section::make('Permisos de Usuarios y Áreas')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions_users')
                            ->label('Acciones sobre Usuarios')
                            ->relationship('permissions', 'name', fn ($query) => $query->where('name', 'like', '%_user'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => str_replace('_user', '', $record->name))
                            ->bulkToggleable()
                            ->columns(3),

                        Forms\Components\CheckboxList::make('permissions_roles')
                            ->label('Acciones sobre Áreas (Roles)')
                            ->relationship('permissions', 'name', fn ($query) => $query->where('name', 'like', '%_role'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => str_replace('_role', '', $record->name))
                            ->bulkToggleable()
                            ->columns(3),
                    ])->collapsible(),

                Forms\Components\Section::make('Permisos de Widgets (Dashboard)')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions_widgets')
                            ->label('Visibilidad de Widgets')
                            ->relationship('permissions', 'name', fn ($query) => $query->where('name', 'like', 'view_widget_%'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => str_replace('view_widget_', '', $record->name))
                            ->bulkToggleable()
                            ->columns(2),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Área')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permisos Asignados')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}

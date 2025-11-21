<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Models\Incident;
use App\Models\Report;
use App\Helpers\CanalesHelper; // <--- Importamos la lista de canales
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Registrar Incidente Rápido';
    protected static ?string $modelLabel = 'Incidente Individual';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Incidente')
                    ->description('Selecciona el tipo de falla para ver los campos específicos.')
                    ->schema([
                        // 1. VINCULACIÓN
                        Forms\Components\Select::make('report_id')
                            ->label('Vincular al Reporte')
                            ->options(fn () => Report::latest()->take(5)->get()->mapWithKeys(fn ($r) => [$r->id => "Reporte {$r->fecha->format('d/m')} - " . ucfirst($r->turno)]))
                            ->default(fn () => Report::latest()->first()?->id)
                            ->required()
                            ->selectablePlaceholder(false),

                        // 2. TIPO DE FALLA
                        Forms\Components\Select::make('tipo_falla')
                            ->label('Tipo de Incidente')
                            ->options([
                                'falla_olt' => '📡 Falla en OLT (Múltiples Tarjetas)',
                                'falla_tv' => '📺 Servidor de TV / Canales',
                                'fibra' => '✂️ Corte de Fibra',
                                'energia' => '⚡ Falla Energía',
                                'equipo_alarmado' => '🚨 Equipo Alarmado (Genérico)',
                                'mantenimiento' => '🛠️ Mantenimiento',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('identificador', null)),

                        // --- ESCENARIO A: FALLA OLT (REPEATER) ---
                        Forms\Components\Group::make()
                            ->visible(fn (Forms\Get $get) => $get('tipo_falla') === 'falla_olt')
                            ->schema([
                                Forms\Components\Select::make('olt_nombre')
                                    ->label('Seleccionar OLT Afectada')
                                    ->options(['Main' => 'OLT Main', 'Backup' => 'OLT Backup'])
                                    ->required()
                                    ->native(false),

                                // Repeater para Tarjetas y Puertos
                                Forms\Components\Repeater::make('olt_afectacion')
                                    ->label('Tarjetas y Puertos Afectados')
                                    ->addActionLabel('Agregar otra Tarjeta')
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            // Selector de Tarjeta (1-17)
                                            Forms\Components\Select::make('tarjeta')
                                                ->label('Tarjeta (Slot)')
                                                ->options(array_combine(range(1, 17), range(1, 17))) 
                                                ->required()
                                                ->searchable(),

                                            // Selector de Puertos Múltiples (1-16)
                                            Forms\Components\Select::make('puertos')
                                                ->label('Puertos Afectados')
                                                ->multiple() 
                                                ->options(array_combine(range(1, 16), range(1, 16)))
                                                ->required()
                                                ->searchable()
                                                ->placeholder('Selecciona puertos...'),
                                        ])
                                    ])
                                    ->columns(1)
                                    ->defaultItems(1)
                                    ->grid(1),
                            ]),

                        // --- ESCENARIO B: FALLA TV (LISTA REAL) ---
                        Forms\Components\Select::make('tv_canales_afectados')
                            ->label('Seleccionar Canales Afectados')
                            ->multiple()
                            ->searchable()
                            ->options(CanalesHelper::getLista()) // <--- LISTA DEL PDF
                            ->visible(fn (Forms\Get $get) => $get('tipo_falla') === 'falla_tv')
                            ->required(),

                        // --- ESCENARIO C: GENÉRICO ---
                        Forms\Components\TextInput::make('identificador')
                            ->label('Identificador del Equipo / Sector')
                            ->placeholder('Ej: Arpón 13-8')
                            ->required()
                            ->visible(fn (Forms\Get $get) => !in_array($get('tipo_falla'), ['falla_olt', 'falla_tv']))
                            ->rule(function (Forms\Get $get) {
                                return Rule::unique('incidents', 'identificador')
                                    ->where('report_id', $get('report_id'))
                                    ->where('tipo_falla', $get('tipo_falla'));
                            }, 'Ya existe un reporte para este equipo.'),

                        // --- CAMPOS COMUNES ---
                        Forms\Components\TextInput::make('barrios')
                            ->label('Barrios Afectados')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('usuarios_afectados')->numeric()->label('Usuarios Afectados'),
                            Forms\Components\Select::make('estado')
                                ->options(['pendiente' => '🔴 Pendiente', 'en_proceso' => '🟠 En Proceso', 'resuelto' => '✅ Resuelto'])
                                ->default('pendiente')
                                ->required(),
                        ]),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Observaciones Adicionales')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('report.fecha')->date('d/m')->label('Fecha'),
                Tables\Columns\TextColumn::make('report.turno')->badge(),
                
                Tables\Columns\TextColumn::make('identificador_visual')
                    ->label('Equipo / Incidente')
                    ->state(function (Incident $record) {
                        // Lógica Visual para la Tabla
                        if ($record->tipo_falla === 'falla_olt') {
                            if (is_array($record->olt_afectacion)) {
                                $countTarjetas = count($record->olt_afectacion);
                                return "OLT {$record->olt_nombre} ({$countTarjetas} Tarjetas)";
                            }
                            return "OLT {$record->olt_nombre}";
                        }
                        if ($record->tipo_falla === 'falla_tv') {
                            $count = count($record->tv_canales_afectados ?? []);
                            return "Servidor TV ({$count} canales)";
                        }
                        return $record->identificador;
                    })
                    ->weight('bold')
                    ->description(fn (Incident $record) => $record->identificador),

                Tables\Columns\TextColumn::make('tipo_falla')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'falla_olt' => 'OLT',
                        'falla_tv' => 'TV Server',
                        'fibra' => 'Fibra',
                        default => ucfirst($state),
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('barrios')->limit(20),
                
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'resuelto' => 'success', 'pendiente' => 'danger', default => 'warning',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),
        ];
    }
}
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    // Icono del menú lateral (opcional, puedes cambiarlo por heroicon-o-clipboard-document-list)
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Reportes NOC';
    protected static ?string $modelLabel = 'Reporte Diario';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SECCIÓN 1: CABECERA DEL REPORTE ---
                Forms\Components\Section::make('Información del Turno')
                    ->description('Datos generales del reporte diario.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('fecha')
                            ->label('Fecha del Reporte')
                            ->required()
                            ->default(now())
                            ->native(false), // Usa el calendario interactivo
                        
                        Forms\Components\Select::make('turno')
                            ->options([
                                'mañana' => '☀️ Mañana (Primer Informe)',
                                'tarde' => '🌤️ Tarde (Segundo Informe)',
                                'noche' => '🌙 Noche (Tercer Informe)',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('ciudad')
                            ->default('Montería')
                            ->disabled() // Generalmente fijo, se puede habilitar si manejan varias sedes
                            ->dehydrated(), // Asegura que se guarde aunque esté deshabilitado
                    ]),

                // --- SECCIÓN 2: INFRAESTRUCTURA CRÍTICA ---
                Forms\Components\Section::make('Estado de Infraestructura')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Toggle::make('concentradores_ok')
                            ->label('Concentradores OK')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false),

                        Forms\Components\Toggle::make('proveedores_ok')
                            ->label('Proveedores OK')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false),

                        Forms\Components\TextInput::make('temp_olt_monteria')
                            ->label('Temp. OLT Montería')
                            ->numeric()
                            ->suffix('°C')
                            ->inputMode('numeric')
                            ->required(),

                        Forms\Components\TextInput::make('temp_olt_backup')
                            ->label('Temp. OLT Backup')
                            ->numeric()
                            ->suffix('°C')
                            ->inputMode('numeric')
                            ->required(),
                    ]),

                // --- SECCIÓN 3: TELEVISIÓN Y SERVIDORES ---
                Forms\Components\Section::make('Televisión y Servidores')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('tv_canales_activos')
                                            ->label('Canales Activos')
                                            ->numeric()
                                            ->default(90)
                                            ->required(),
                                        
                                        Forms\Components\TextInput::make('tv_canales_total')
                                            ->label('Total Canales')
                                            ->numeric()
                                            ->default(92)
                                            ->readOnly(),
                                    ]),
                                
                                // TagsInput permite escribir "NatGeo", presionar Enter y agregar otro.
                                Forms\Components\TagsInput::make('tv_canales_offline')
                                    ->label('Canales Sin Señal')
                                    ->placeholder('Escribe el nombre y presiona Enter')
                                    ->color('danger')
                                    ->suggestions([
                                        'National Geographic',
                                        'Oromar TV',
                                        'Discovery Theater',
                                        'Cristovisión',
                                    ]),
                            ]),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Toggle::make('intalflix_online')
                                    ->label('Servidor Intalflix Online')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),
                                
                                Forms\Components\Textarea::make('novedades_servidores')
                                    ->label('Novedades Servidores')
                                    ->placeholder('Ej: Reinicio programado, latencia alta...')
                                    ->rows(3),
                            ]),
                    ]),

                // --- SECCIÓN 4: INCIDENTES Y NOVEDADES (REPEATER) ---
                Forms\Components\Section::make('Novedades e Incidentes')
                    ->description('Registra aquí cortes de fibra, fallas de energía o problemas específicos por sector.')
                    ->schema([
                        Forms\Components\Repeater::make('incidents')
                            ->label('Listado de Incidentes')
                            ->relationship() // ¡Magia! Guarda automáticamente en la tabla incidents
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('identificador')
                                            ->label('Equipo / Puerto')
                                            ->placeholder('Ej: Arpón 13-8')
                                            ->required(),
                                        
                                        Forms\Components\Select::make('tipo_falla')
                                            ->options([
                                                'fibra' => '✂️ Corte de Fibra',
                                                'energia' => '⚡ Falla Energía',
                                                'potencia' => '📉 Potencia / Atenuación',
                                                'equipo_alarmado' => '🚨 Equipo Alarmado',
                                                'mantenimiento' => '🛠️ Mantenimiento',
                                            ])
                                            ->required(),

                                        Forms\Components\Select::make('estado')
                                            ->options([
                                                'pendiente' => '🔴 Pendiente',
                                                'en_proceso' => '🟠 En Proceso',
                                                'resuelto' => '✅ Resuelto',
                                            ])
                                            ->default('pendiente')
                                            ->required(),
                                    ]),
                                
                                Forms\Components\TextInput::make('barrios')
                                    ->label('Barrios Afectados')
                                    ->placeholder('Ej: Urb. Berlín, El Portal')
                                    ->columnSpan(2)
                                    ->required(),
                                
                                Forms\Components\TextInput::make('usuarios_afectados')
                                    ->label('Usuarios Afectados')
                                    ->numeric()
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('descripcion')
                                    ->label('Descripción Detallada')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['identificador'] ?? null) // Muestra el nombre en el acordeón
                            ->collapsible() // Permite colapsar items para ahorrar espacio
                            ->columns(3),
                            
                        Forms\Components\Textarea::make('observaciones_generales')
                            ->label('Observaciones Generales (Opcional)')
                            ->rows(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('turno')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mañana' => 'warning',
                        'tarde' => 'info',
                        'noche' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('temp_olt_monteria')
                    ->label('Temp OLT')
                    ->suffix('°C')
                    ->numeric(),
                Tables\Columns\IconColumn::make('concentradores_ok')
                    ->boolean(),
                Tables\Columns\TextColumn::make('incidents_count')
                    ->label('Incidentes')
                    ->counts('incidents')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                // Aquí podrás filtrar por fecha o turno más adelante
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
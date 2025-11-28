<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Filament\Resources\IncidentResource\RelationManagers;
use App\Models\Incident;
use App\Models\Report;
use App\Helpers\CanalesHelper; // <--- Importamos la lista de canales
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Tickets';
    protected static ?string $navigationLabel = 'Tickets Creados';
    protected static ?string $modelLabel = 'Crear Tickets de Incidente';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Incidente')
                    ->description('Selecciona el tipo de falla para ver los campos específicos.')
                    ->schema([
                        // 1. SELECCIÓN DE CIUDAD (Automática y Oculta)
                        Forms\Components\Select::make('ciudad_selector')
                            ->label('Ciudad / Sede')
                            ->options([
                                'monteria' => 'Montería',
                                'puerto_libertador' => 'Puerto Libertador',
                                'regional' => 'Sedes Regionales (Valencia, Tierralta, San Pedro)',
                            ])
                            ->default(function () {
                                $city = auth()->user()->city;
                                if (in_array($city, ['valencia', 'tierralta', 'san_pedro'])) {
                                    return 'regional';
                                }
                                return $city ?? 'monteria';
                            })
                            ->live()
                            ->dehydrated(false) 
                            ->hidden() // Oculto al usuario
                            ->afterStateHydrated(function (Forms\Components\Select $component, ?Incident $record) {
                                if ($record) {
                                    if ($record->report_puerto_libertador_id) {
                                        $component->state('puerto_libertador');
                                    } elseif ($record->report_regional_id) {
                                        $component->state('regional');
                                    } else {
                                        $component->state('monteria');
                                    }
                                } else {
                                    // Default al crear
                                    $city = auth()->user()->city;
                                    if (in_array($city, ['valencia', 'tierralta', 'san_pedro'])) {
                                        $component->state('regional');
                                    } else {
                                        $component->state($city ?? 'monteria');
                                    }
                                }
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::updateReportIds($set, $get('ciudad_selector'));
                            }),

                        // 1.1 VINCULACIÓN MONTERÍA (Automática y Oculta)
                        Forms\Components\Select::make('report_id')
                            ->label('Vincular al Reporte (Montería)')
                            ->options(fn () => Report::latest()->take(5)->get()->mapWithKeys(fn ($r) => [$r->id => "Reporte {$r->fecha->format('d/m')} - " . ucfirst($r->turno)]))
                            ->default(fn () => Report::latest()->first()?->id)
                            ->required(fn (Forms\Get $get) => $get('ciudad_selector') === 'monteria')
                            ->hidden() // Oculto
                            ->selectablePlaceholder(false),

                        // 1.2 VINCULACIÓN PUERTO LIBERTADOR (Automática y Oculta)
                        Forms\Components\Select::make('report_puerto_libertador_id')
                            ->label('Vincular al Reporte (Puerto Libertador)')
                            ->options(fn () => \App\Models\ReportPuertoLibertador::latest()->take(5)->get()->mapWithKeys(fn ($r) => [$r->id => "Reporte {$r->fecha->format('d/m')} - " . ucfirst($r->turno)]))
                            ->default(fn () => \App\Models\ReportPuertoLibertador::latest()->first()?->id)
                            ->required(fn (Forms\Get $get) => $get('ciudad_selector') === 'puerto_libertador')
                            ->hidden() // Oculto
                            ->selectablePlaceholder(false),

                        // 1.3 VINCULACIÓN REGIONAL (Automática y Oculta)
                        Forms\Components\Select::make('report_regional_id')
                            ->label('Vincular al Reporte (Regional)')
                            ->options(fn () => \App\Models\ReportRegional::latest()->take(5)->get()->mapWithKeys(fn ($r) => [$r->id => "Reporte {$r->fecha->format('d/m')} - " . ucfirst($r->turno)]))
                            ->default(fn () => \App\Models\ReportRegional::latest()->first()?->id)
                            ->required(fn (Forms\Get $get) => $get('ciudad_selector') === 'regional')
                            ->hidden() // Oculto
                            ->selectablePlaceholder(false),

                        // 2. TIPO DE FALLA
                        Forms\Components\Select::make('tipo_falla')
                            ->label('Tipo de Incidente')
                            ->options(function (Forms\Get $get) {
                                $city = $get('ciudad_selector');
                                
                                if ($city === 'puerto_libertador') {
                                    return [
                                        'falla_tv' => '📺 Servidor de TV / Canales',
                                        'internet_falla_general' => '🌐 Internet Falla General',
                                        'internet_falla_especifica' => '👤 Internet Falla Usuario Específico',
                                        'otros' => '📝 Otros (Incidentes Varios)',
                                    ];
                                }

                                return [
                                    'falla_olt' => '📡 Falla en OLT (Múltiples Tarjetas)',
                                    'falla_tv' => '📺 Servidor de TV / Canales',
                                    'internet_falla_general' => '🌐 Internet Falla General',
                                    'internet_falla_especifica' => '👤 Internet Falla Usuario Específico',
                                ];
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                $set('identificador', null);
                                if ($state === 'internet_falla_especifica') {
                                    $set('usuarios_afectados', [
                                        ['nombre' => '', 'cedula' => '', 'barrio' => '', 'ip' => '']
                                    ]);
                                } elseif ($state === 'internet_falla_general') {
                                    $set('usuarios_afectados', array_fill(0, 2, ['nombre' => '', 'cedula' => '', 'barrio' => '', 'ip' => '']));
                                }
                            }),

                        // --- ESCENARIO D: INTERNET FALLA GENERAL ---
                        Forms\Components\Repeater::make('usuarios_afectados')
                            ->label(fn (Forms\Get $get) => $get('tipo_falla') === 'internet_falla_especifica' ? 'Datos del Usuario' : 'Usuarios Afectados')
                            ->schema([
                                Forms\Components\TextInput::make('nombre')->label('Nombre'),
                                Forms\Components\TextInput::make('cedula')->label('Cédula')->required(),
                                Forms\Components\TextInput::make('barrio')->label('Barrio'),
                                Forms\Components\TextInput::make('ip')->label('IP')->ipv4(),
                            ])
                            ->columns(2)
                            ->defaultItems(fn (Forms\Get $get) => $get('tipo_falla') === 'internet_falla_especifica' ? 1 : 2)
                            ->maxItems(fn (Forms\Get $get) => $get('tipo_falla') === 'internet_falla_especifica' ? 1 : null)
                            ->reorderable(fn (Forms\Get $get) => $get('tipo_falla') !== 'internet_falla_especifica')
                            ->deletable(fn (Forms\Get $get) => $get('tipo_falla') !== 'internet_falla_especifica')
                            ->addable(fn (Forms\Get $get) => $get('tipo_falla') !== 'internet_falla_especifica')
                            ->visible(fn (Forms\Get $get) => in_array($get('tipo_falla'), ['internet_falla_general', 'internet_falla_especifica']))
                            ->required(),

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
                            ->visible(fn (Forms\Get $get) => !in_array($get('tipo_falla'), [
                                'falla_olt', 
                                'falla_tv',
                                'internet_falla_general',
                                'internet_falla_especifica',
                                'otros'
                            ]))
                            ->rule(function (Forms\Get $get) {
                                return Rule::unique('incidents', 'identificador')
                                    ->where('report_id', $get('report_id'))
                                    ->where('tipo_falla', $get('tipo_falla'));
                            }, 'Ya existe un reporte para este equipo.'),

                        // --- CAMPOS COMUNES ---
                        Forms\Components\Select::make('estado')
                            ->options(['pendiente' => '🔴 Pendiente', 'en_proceso' => '🟠 En Proceso', 'resuelto' => '✅ Resuelto'])
                            ->default('pendiente')
                            ->required()
                            ->disabled(),

                        Forms\Components\Select::make('responsibles')
                            ->label('Asignar Responsables (Tickets)')
                            ->relationship('responsibles', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('configuracion_especial')
                            ->label('Configuración Especial')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('tipo_falla') === 'internet_falla_especifica'),

                        Forms\Components\Section::make('Evidencias (Creación)')
                            ->schema([
                                Forms\Components\FileUpload::make('photos_creation')
                                    ->label('Fotos del Incidente')
                                    ->multiple()
                                    ->image()
                                    ->imageEditor()
                                    ->directory('incident-creation-photos')
                                    ->columnSpanFull(),
                            ])->collapsible(),

                        Forms\Components\Section::make('Evidencias de Solución')
                            ->schema([
                                Forms\Components\FileUpload::make('photos_resolution')
                                    ->label('Fotos de la Solución')
                                    ->multiple()
                                    ->image()
                                    ->imageEditor()
                                    ->directory('incident-resolution-photos')
                                    ->columnSpanFull()
                                    ->disabled(), // Solo lectura en la vista general
                            ])
                            ->collapsible()
                            ->visible(fn (?Incident $record) => $record && !empty($record->photos_resolution)),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Observaciones Adicionales')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('responsibles')
                            ->label('Asignar Responsables (Tickets)')
                            ->relationship('responsibles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),
                        
                        Forms\Components\ViewField::make('timeline')
                            ->view('filament.components.incident-timeline')
                            ->viewData([
                                'record' => $form->getRecord(),
                            ])
                            ->hidden(fn (?Incident $record) => $record === null)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket #')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('ciudad_origen')
                    ->label('Ciudad / Sede')
                    ->state(function (Incident $record) {
                        if ($record->report_puerto_libertador_id) return 'Puerto Libertador';
                        if ($record->report_regional_id) return 'Regional';
                        return 'Montería';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Puerto Libertador' => 'info',
                        'Regional' => 'warning',
                        'Montería' => 'success',
                        default => 'gray',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("CASE 
                            WHEN report_puerto_libertador_id IS NOT NULL THEN 1 
                            WHEN report_regional_id IS NOT NULL THEN 2 
                            ELSE 0 END $direction");
                    }),

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

                Tables\Columns\TextColumn::make('responsibles.name')
                    ->label('Responsables')
                    ->badge()
                    ->color('info')
                    ->limitList(2),

                Tables\Columns\TextColumn::make('tipo_falla')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'falla_olt' => 'OLT',
                        'falla_tv' => 'TV Server',
                        'fibra' => 'Fibra',
                        'falla_mikrotik' => 'Mikrotik',
                        'falla_enlace' => 'Enlace',
                        'falla_general' => 'Falla General',
                        'internet_falla_general' => 'Internet General',
                        'internet_falla_especifica' => 'Usuario Específico',
                        default => ucfirst($state),
                    })
                    ->badge(),

                
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Detalle')
                    ->limit(30)
                    ->tooltip(fn (Incident $record): string => $record->descripcion ?? '')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'resuelto' => 'success', 'pendiente' => 'danger', default => 'warning',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null)
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En Proceso',
                        'resuelto' => 'Resuelto',
                    ]),
                Tables\Filters\SelectFilter::make('ciudad')
                    ->label('Filtrar por Ciudad')
                    ->options([
                        'monteria' => 'Montería',
                        'puerto_libertador' => 'Puerto Libertador',
                        'regional' => 'Sedes Regionales',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'monteria') {
                            return $query->whereNotNull('report_id');
                        }
                        if ($data['value'] === 'puerto_libertador') {
                            return $query->whereNotNull('report_puerto_libertador_id');
                        }
                        if ($data['value'] === 'regional') {
                            return $query->whereNotNull('report_regional_id');
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('share')
                        ->label('Compartir')
                        ->icon('heroicon-o-share')
                        ->color('info')
                        ->modalHeading('Compartir Ticket')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                        ->form([
                            Forms\Components\TextInput::make('copy_text')
                                ->label('Copia este texto:')
                                ->default(function (Incident $record) {
                                    $url = \App\Filament\Resources\AssignedIncidentResource::getUrl('view', ['record' => $record]);
                                    return "Ticket #{$record->ticket_number} - {$url}";
                                })
                                ->extraInputAttributes([
                                    'readonly' => true,
                                    'class' => 'cursor-pointer',
                                    'x-on:click' => "
                                        \$el.select();
                                        document.execCommand('copy');
                                        new FilamentNotification().title('Copiado al portapapeles').success().send();
                                    ",
                                ])
                                ->columnSpanFull(),
                        ]),

                    Tables\Actions\ViewAction::make(),
                    
                    Tables\Actions\Action::make('asignar_responsable')
                        ->label('Asignar')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->visible(fn (Incident $record) => $record->estado === 'pendiente')
                        ->form([
                            Forms\Components\Select::make('responsibles')
                                ->label('Asignar a:')
                                ->multiple()
                                ->options(\App\Models\User::all()->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (Incident $record, array $data) {
                            // Attach users with pivot data
                            $record->responsibles()->syncWithPivotValues($data['responsibles'], [
                                'status' => 'pending',
                                'assigned_by' => auth()->id(),
                                'assigned_at' => now(),
                            ], false); // false = no detach existing, just add/update
                            
                            // Enviar Notificaciones a la Base de Datos
                            foreach ($data['responsibles'] as $userId) {
                                $user = \App\Models\User::find($userId);
                                if ($user) {
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

                            \Filament\Notifications\Notification::make()
                                ->title('Responsables asignados correctamente')
                                ->success()
                                ->send();
                        })
                ])
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([
                // Tables\Actions\DeleteBulkAction::make()
                //     ->visible(fn () => auth()->user()->hasRole('super_admin')),
            ])]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ResponsiblesRelationManager::class, // Replaced by Timeline
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user->hasRole('super_admin') || $user->can('view_all_incidents')) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'view' => Pages\ViewIncident::route('/{record}'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),
        ];
    }

    protected static function updateReportIds(Forms\Set $set, ?string $city): void
    {
        $set('report_id', null);
        $set('report_puerto_libertador_id', null);
        $set('report_regional_id', null);

        if ($city === 'monteria') {
            $latest = Report::latest()->first();
            if ($latest) $set('report_id', $latest->id);
        } elseif ($city === 'puerto_libertador') {
            $latest = \App\Models\ReportPuertoLibertador::latest()->first();
            if ($latest) $set('report_puerto_libertador_id', $latest->id);
        } elseif ($city === 'regional') {
            $latest = \App\Models\ReportRegional::latest()->first();
            if ($latest) $set('report_regional_id', $latest->id);
        }
    }
}
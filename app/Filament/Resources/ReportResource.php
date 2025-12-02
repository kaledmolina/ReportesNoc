<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use App\Models\Incident;
use App\Helpers\CanalesHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Reportes NOC Monteria';
    protected static ?string $modelLabel = 'Reporte Diario';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ==========================================
                // SECCIÓN 1: INFORMACIÓN GENERAL
                // ==========================================
                Forms\Components\Section::make('Información del Turno')
                    ->description('Datos generales del reporte diario.')
                    ->icon('heroicon-m-information-circle')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Forms\Components\DatePicker::make('fecha')
                            ->label('Fecha')
                            ->required()
                            ->default(now())
                            ->native(false),
                        Forms\Components\Select::make('turno')
                            ->label('Turno')
                            ->options([
                                'mañana' => '☀️ Mañana',
                                'tarde' => '🌤️ Tarde',
                                'noche' => '🌙 Noche'
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('ciudad')
                            ->default('Montería')
                            ->disabled()
                            ->dehydrated(),
                    ]),

                // ==========================================
                // SECCIÓN 2: INFRAESTRUCTURA (Concentradores y Proveedores)
                // ==========================================
                Forms\Components\Section::make('Estado de Conectividad')
                    ->description('Monitoreo de Concentradores y Proveedores de servicio.')
                    ->icon('heroicon-m-globe-alt')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            // Columna 1: Concentradores
                            Forms\Components\Repeater::make('lista_concentradores')
                                ->label('Concentradores')
                                ->schema([
                                    Forms\Components\Grid::make(12)->schema([
                                        Forms\Components\TextInput::make('nombre')->hiddenLabel()->required()->placeholder('Nombre')->columnSpan(4),
                                        Forms\Components\Toggle::make('estado')->label('Óptimo')->default(true)->onColor('success')->offColor('danger')->inline(false)->columnSpan(2),
                                        Forms\Components\TextInput::make('detalle')->label('Detalle')->placeholder('Ej: Lentitud...')->columnSpan(6),
                                    ])
                                ])
                                ->default([
                                    ['nombre' => 'Concentrador 1', 'estado' => true], ['nombre' => 'Concentrador 2', 'estado' => true],
                                    ['nombre' => 'Concentrador 3', 'estado' => true], ['nombre' => 'Concentrador 4', 'estado' => true],
                                    ['nombre' => 'Concentrador 5', 'estado' => true], ['nombre' => 'Concentrador 6', 'estado' => true],                                    
                                    ['nombre' => 'Visión Total', 'estado' => true], ['nombre' => 'Visión Total 28', 'estado' => true],
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null),

                            // Columna 2: Proveedores
                            Forms\Components\Repeater::make('lista_proveedores')
                                ->label('Proveedores')
                                ->schema([
                                    Forms\Components\Grid::make(12)->schema([
                                        Forms\Components\TextInput::make('nombre')->hiddenLabel()->required()->placeholder('Nombre')->columnSpan(3),
                                        Forms\Components\Toggle::make('estado')->label('Enlazado')->default(true)->onColor('success')->offColor('danger')->inline(false)->columnSpan(2),
                                        Forms\Components\TextInput::make('consumo')->label('Consumo')->placeholder('Ej: 4.2 Gbps')->columnSpan(3),
                                        Forms\Components\TextInput::make('detalle')->label('Novedad')->placeholder('Ej: Caída...')->columnSpan(4),
                                    ])
                                ])
                                ->default([
                                    ['nombre' => 'Ufinet', 'estado' => true], ['nombre' => 'Cirion', 'estado' => true], ['nombre' => 'Somos', 'estado' => true],
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null),
                        ]),
                    ]),

                // ==========================================
                // SECCIÓN 3: OLTs (Lógica Compleja)
                // ==========================================
                Forms\Components\Section::make('Estado de OLTs (Sincronizado)')
                    ->description('Si reportas una falla, el sistema verifica tickets existentes automáticamente.')
                    ->icon('heroicon-m-server-stack')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            
                            // --- OLT MAIN ---
                            Forms\Components\Section::make('OLT Montería (Main)')
                                ->icon('heroicon-o-cpu-chip')
                                ->compact() // Diseño más compacto
                                ->schema([
                                    Forms\Components\TextInput::make('temp_olt_monteria')
                                        ->label('Temperatura')
                                        ->numeric()
                                        ->placeholder('29')
                                        ->suffix('°C')
                                        ->required(),
                                    
                                    Forms\Components\Repeater::make('olt_monteria_detalle')
                                        ->label('Detalle Tarjetas')
                                        ->defaultItems(0)
                                        ->addActionLabel('Agregar Tarjeta')
                                        ->schema([
                                            Forms\Components\Grid::make(12)->schema([
                                                Forms\Components\Select::make('tarjeta')
                                                    ->label('Tarjeta')
                                                    ->options(array_combine(range(1, 17), range(1, 17)))
                                                    ->required()
                                                    ->columnSpan(4)
                                                    ->live(),
                                                
                                                Forms\Components\Toggle::make('estado')
                                                    ->label('Óptimo')
                                                    ->default(false)
                                                    ->onColor('success')->offColor('danger')->inline(false)->columnSpan(3)
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                                        if ($state === false) {
                                                            $tarjeta = $get('tarjeta');
                                                            $puertosForm = array_map('strval', (array)($get('puertos') ?? []));
                                                            if (!$tarjeta) return;

                                                            $existe = Incident::where('tipo_falla', 'falla_olt')
                                                                ->where('olt_nombre', 'Main')
                                                                ->where('estado', '!=', 'resuelto')
                                                                ->get()
                                                                ->contains(function ($inc) use ($tarjeta, $puertosForm) {
                                                                    foreach($inc->olt_afectacion ?? [] as $afec) {
                                                                        if ((string)($afec['tarjeta'] ?? '') === (string)$tarjeta) {
                                                                            $puertosInc = array_map('strval', (array)($afec['puertos'] ?? []));
                                                                            if (empty($puertosForm) && empty($puertosInc)) return true;
                                                                            if (count(array_intersect($puertosForm, $puertosInc)) > 0) return true;
                                                                        }
                                                                    }
                                                                    return false;
                                                                });

                                                            if ($existe) {
                                                                Notification::make()->title('Ticket Detectado')->body("Ya existe un ticket abierto para estos puertos en Tarjeta {$tarjeta}.")->info()->send();
                                                                $set('incidente_vinculado', true);
                                                            } else {
                                                                $set('incidente_vinculado', false);
                                                            }
                                                        }
                                                    }),

                                                Forms\Components\Select::make('puertos')->label('Puertos')
                                                    ->multiple()
                                                    ->options(array_combine(range(1, 16), range(1, 16)))
                                                    ->placeholder('Selecc.')
                                                    ->columnSpan(5)
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                                        if ($get('estado') === false) {
                                                            $tarjeta = $get('tarjeta');
                                                            $puertosForm = array_map('strval', (array)($get('puertos') ?? []));
                                                            if (!$tarjeta) return;
                                                            
                                                            $existe = Incident::where('tipo_falla', 'falla_olt')->where('olt_nombre', 'Main')->where('estado', '!=', 'resuelto')->get()
                                                                ->contains(function ($inc) use ($tarjeta, $puertosForm) {
                                                                    foreach($inc->olt_afectacion ?? [] as $afec) {
                                                                        if ((string)($afec['tarjeta'] ?? '') === (string)$tarjeta) {
                                                                            $puertosInc = array_map('strval', (array)($afec['puertos'] ?? []));
                                                                            if (empty($puertosForm) && empty($puertosInc)) return true;
                                                                            if (count(array_intersect($puertosForm, $puertosInc)) > 0) return true;
                                                                        }
                                                                    }
                                                                    return false;
                                                                });
                                                            $set('incidente_vinculado', $existe);
                                                        }
                                                    }),

                                                Forms\Components\TextInput::make('detalle')->label('Observación')->placeholder('Falla...')->columnSpan(12),

                                                Forms\Components\Hidden::make('responsible_id'),

                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('generar_ticket')
                                                        ->label('Generar Ticket')
                                                        ->icon('heroicon-m-ticket')
                                                        ->color('warning')
                                                        ->form([
                                                            Forms\Components\Select::make('responsible_id')
                                                                ->label('Asignar Responsable')
                                                                ->options(\App\Models\User::all()->pluck('name', 'id'))
                                                                ->required()
                                                                ->searchable()
                                                                ->preload(),
                                                        ])
                                                        ->action(function (array $data, Forms\Get $get, Forms\Set $set, $livewire) {
                                                            $tarjeta = $get('tarjeta');
                                                            $puertos = $get('puertos') ?? [];
                                                            $responsibleId = $data['responsible_id'];

                                                            if (!$tarjeta) { Notification::make()->title('Selecciona tarjeta')->danger()->send(); return; }

                                                            if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                                                                Notification::make()->title('Ticket en Cola')->body('Se creará al guardar.')->success()->send();
                                                                $set('incidente_vinculado', true);
                                                                $set('responsible_id', $responsibleId);
                                                            } else {
                                                                $reporte = $livewire->record; 
                                                                $incident = Incident::create([
                                                                    'report_id' => $reporte->id, 'tipo_falla' => 'falla_olt', 'olt_nombre' => 'Main',
                                                                    'olt_afectacion' => [['tarjeta' => $tarjeta, 'puertos' => $puertos]],
                                                                    'barrios' => 'N/A (Auto)', 'estado' => 'pendiente',
                                                                    'descripcion' => 'Generado manualmente.',
                                                                    'identificador' => "OLT Main - T{$tarjeta}"
                                                                ]);
                                                                
                                                                $incident->responsibles()->attach($responsibleId, [
                                                                    'status' => 'pending',
                                                                    'assigned_by' => auth()->id(),
                                                                    'assigned_at' => now(),
                                                                ]);

                                                                Notification::make()->title('Ticket Creado')->success()->send();
                                                                $set('incidente_vinculado', true);
                                                            }
                                                        }),
                                                ])
                                                ->visible(function (Forms\Get $get) {
                                                    if ($get('estado') === true) return false;
                                                    if ($get('incidente_vinculado')) return false;

                                                    $tarjeta = $get('tarjeta');
                                                    $puertosForm = array_map('strval', (array)($get('puertos') ?? []));

                                                    if (!$tarjeta) return false;

                                                    $existe = Incident::where('tipo_falla', 'falla_olt')
                                                        ->where('olt_nombre', 'Main')
                                                        ->where('estado', '!=', 'resuelto')
                                                        ->get()
                                                        ->contains(function ($inc) use ($tarjeta, $puertosForm) {
                                                            foreach($inc->olt_afectacion ?? [] as $afec) {
                                                                if ((string)($afec['tarjeta'] ?? '') === (string)$tarjeta) {
                                                                    $puertosInc = array_map('strval', (array)($afec['puertos'] ?? []));
                                                                    if (empty($puertosForm) && empty($puertosInc)) return true;
                                                                    if (count(array_intersect($puertosForm, $puertosInc)) > 0) return true;
                                                                }
                                                            }
                                                            return false;
                                                        });
                                                    
                                                    return !$existe;
                                                })
                                                ->columnSpan(12),

                                                Forms\Components\Placeholder::make('aviso_reportado')
                                                    ->label('')
                                                    ->content('✅ Ticket de incidente ya existente para estos puertos.')
                                                    ->visible(function (Forms\Get $get) {
                                                        if ($get('estado') === true) return false;
                                                        $tarjeta = $get('tarjeta');
                                                        $puertosForm = array_map('strval', (array)($get('puertos') ?? []));
                                                        if (!$tarjeta) return false;

                                                        return Incident::where('tipo_falla', 'falla_olt')
                                                            ->where('olt_nombre', 'Main')
                                                            ->where('estado', '!=', 'resuelto')
                                                            ->get()
                                                            ->contains(function ($inc) use ($tarjeta, $puertosForm) {
                                                                foreach($inc->olt_afectacion ?? [] as $afec) {
                                                                    if ((string)($afec['tarjeta'] ?? '') === (string)$tarjeta) {
                                                                        $puertosInc = array_map('strval', (array)($afec['puertos'] ?? []));
                                                                        if (empty($puertosForm) && empty($puertosInc)) return true;
                                                                        if (count(array_intersect($puertosForm, $puertosInc)) > 0) return true;
                                                                    }
                                                                }
                                                                return false;
                                                            });
                                                    })
                                                    ->extraAttributes(['class' => 'text-green-600 font-bold text-sm bg-green-50 p-2 rounded border border-green-200'])
                                                    ->columnSpan(12),

                                                Forms\Components\Placeholder::make('aviso_cola')
                                                    ->label('')
                                                    ->content('🕒 Ticket programado para creación.')
                                                    ->visible(fn (Forms\Get $get) => $get('incidente_vinculado') === true && $get('estado') === false)
                                                    ->extraAttributes(['class' => 'text-blue-600 font-bold text-xs bg-blue-50 p-2 rounded'])
                                                    ->columnSpan(12),

                                                Forms\Components\Hidden::make('incidente_vinculado')->default(false),
                                            ])
                                        ])->collapsed()->itemLabel(fn ($state) => 'Tarjeta ' . ($state['tarjeta'] ?? '?')),
                                ]),

                            // --- OLT BACKUP ---
                            Forms\Components\Section::make('OLT Backup')
                                ->icon('heroicon-o-server')
                                ->compact()
                                ->schema([
                                    Forms\Components\TextInput::make('temp_olt_backup')->label('Temperatura')->numeric()->placeholder('27')->suffix('°C')->required(),
                                    
                                    Forms\Components\Repeater::make('olt_backup_detalle')
                                        ->label('Detalle Tarjetas')
                                        ->defaultItems(0)
                                        ->addActionLabel('Agregar Tarjeta')
                                        ->schema([
                                            Forms\Components\Grid::make(12)->schema([
                                                Forms\Components\Select::make('tarjeta')->label('Tarjeta')->options(array_combine(range(1, 17), range(1, 17)))->required()->columnSpan(4)->live(),
                                                Forms\Components\Toggle::make('estado')->label('Óptimo')->default(false)->onColor('success')->offColor('danger')->inline(false)->columnSpan(3)->live()
                                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                                        if ($state === false) {
                                                            $tarjeta = $get('tarjeta');
                                                            $puertosForm = array_map('strval', (array)($get('puertos') ?? []));
                                                            if (!$tarjeta) return;

                                                            $existe = Incident::where('tipo_falla', 'falla_olt')
                                                                ->where('olt_nombre', 'Backup')
                                                                ->where('estado', '!=', 'resuelto')
                                                                ->get()
                                                                ->contains(function ($inc) use ($tarjeta, $puertosForm) {
                                                                    foreach($inc->olt_afectacion ?? [] as $afec) {
                                                                        if ((string)($afec['tarjeta'] ?? '') === (string)$tarjeta) {
                                                                            $puertosInc = array_map('strval', (array)($afec['puertos'] ?? []));
                                                                            if (empty($puertosForm) && empty($puertosInc)) return true;
                                                                            if (count(array_intersect($puertosForm, $puertosInc)) > 0) return true;
                                                                        }
                                                                    }
                                                                    return false;
                                                                });

                                                            if ($existe) {
                                                                Notification::make()->title('Ticket Detectado')->info()->send();
                                                                $set('incidente_vinculado', true);
                                                            } else {
                                                                $set('incidente_vinculado', false);
                                                            }
                                                        }
                                                    }),

                                                Forms\Components\Select::make('puertos')->label('Puertos')
                                                    ->multiple()
                                                    ->options(array_combine(range(1, 16), range(1, 16)))
                                                    ->placeholder('Selecc.')
                                                    ->columnSpan(5)
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                                        if ($get('estado') === false) {
                                                            $tarjeta = $get('tarjeta');
                                                            $puertosForm = array_map('strval', (array)($get('puertos') ?? []));
                                                            if (!$tarjeta) return;
                                                            
                                                            $existe = Incident::where('tipo_falla', 'falla_olt')->where('olt_nombre', 'Backup')->where('estado', '!=', 'resuelto')->get()
                                                                ->contains(function ($inc) use ($tarjeta, $puertosForm) {
                                                                    foreach($inc->olt_afectacion ?? [] as $afec) {
                                                                        if ((string)($afec['tarjeta'] ?? '') === (string)$tarjeta) {
                                                                            $puertosInc = array_map('strval', (array)($afec['puertos'] ?? []));
                                                                            if (empty($puertosForm) && empty($puertosInc)) return true;
                                                                            if (count(array_intersect($puertosForm, $puertosInc)) > 0) return true;
                                                                        }
                                                                    }
                                                                    return false;
                                                                });
                                                            $set('incidente_vinculado', $existe);
                                                        }
                                                    }),

                                                Forms\Components\TextInput::make('detalle')->label('Observación')->placeholder('Falla...')->columnSpan(12),
                                                
                                                Forms\Components\Hidden::make('responsible_id'),

                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('generar_ticket_backup')
                                                        ->label('Generar Ticket')
                                                        ->icon('heroicon-m-ticket')
                                                        ->color('warning')
                                                        ->form([
                                                            Forms\Components\Select::make('responsible_id')
                                                                ->label('Asignar Responsable')
                                                                ->options(\App\Models\User::all()->pluck('name', 'id'))
                                                                ->required()
                                                                ->searchable()
                                                                ->preload(),
                                                        ])
                                                        ->action(function (array $data, Forms\Get $get, Forms\Set $set, $livewire) {
                                                            $responsibleId = $data['responsible_id'];
                                                            if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                                                                Notification::make()->title('Ticket en Cola')->body('Se creará al guardar.')->success()->send();
                                                                $set('incidente_vinculado', true);
                                                                $set('responsible_id', $responsibleId);
                                                            } else {
                                                                $tarjeta = $get('tarjeta'); $puertos = $get('puertos') ?? [];
                                                                $incident = Incident::create([ 'report_id' => $livewire->record->id, 'tipo_falla' => 'falla_olt', 'olt_nombre' => 'Backup', 'olt_afectacion' => [['tarjeta' => $tarjeta, 'puertos' => $puertos]], 'barrios' => 'N/A', 'estado' => 'pendiente', 'descripcion' => 'Auto-generado OLT Backup.', 'identificador' => "OLT Backup - T{$tarjeta}" ]);
                                                                
                                                                $incident->responsibles()->attach($responsibleId, [
                                                                    'status' => 'pending',
                                                                    'assigned_by' => auth()->id(),
                                                                    'assigned_at' => now(),
                                                                ]);

                                                                Notification::make()->title('Ticket Creado')->success()->send();
                                                                $set('incidente_vinculado', true);
                                                            }
                                                        }),
                                                ])
                                                ->visible(function (Forms\Get $get) {
                                                    if ($get('estado') === true) return false;
                                                    if ($get('incidente_vinculado')) return false;
                                                    $tarjeta = $get('tarjeta'); $puertosForm = array_map('strval', (array)($get('puertos') ?? []));
                                                    if (!$tarjeta) return false;

                                                    return !Incident::where('tipo_falla', 'falla_olt')->where('olt_nombre', 'Backup')->where('estado', '!=', 'resuelto')->get()->contains(function ($inc) use ($tarjeta, $puertosForm) {
                                                        foreach($inc->olt_afectacion ?? [] as $afec) {
                                                            if ((string)($afec['tarjeta'] ?? '') === (string)$tarjeta) {
                                                                $puertosInc = array_map('strval', (array)($afec['puertos'] ?? []));
                                                                if (empty($puertosForm) && empty($puertosInc)) return true;
                                                                if (count(array_intersect($puertosForm, $puertosInc)) > 0) return true;
                                                            }
                                                        }
                                                        return false;
                                                    });
                                                })
                                                ->columnSpan(12),
                                                
                                                Forms\Components\Placeholder::make('aviso_reportado_backup')->label('')->content('✅ Ticket de incidente ya existente para estos puertos.')->visible(fn (Forms\Get $get) => $get('estado') === false && Incident::where('tipo_falla', 'falla_olt')->where('olt_nombre', 'Backup')->where('estado', '!=', 'resuelto')->get()->contains(fn ($inc) => collect($inc->olt_afectacion ?? [])->contains(fn($afec) => ($afec['tarjeta']??null) == $get('tarjeta') && count(array_intersect($get('puertos')??[], $afec['puertos']??[]))>0)))->extraAttributes(['class' => 'text-green-600 font-bold text-sm bg-green-50 p-2 rounded border border-green-200'])->columnSpan(12),
                                                Forms\Components\Placeholder::make('aviso_cola')->label('')->content('🕒 Ticket programado.')->visible(fn (Forms\Get $get) => $get('incidente_vinculado') === true && $get('estado') === false)->extraAttributes(['class' => 'text-blue-600 font-bold text-xs bg-blue-50 p-2 rounded'])->columnSpan(12),
                                                Forms\Components\Hidden::make('incidente_vinculado')->default(false),
                                            ])
                                        ])->collapsed()->itemLabel(fn ($state) => 'Tarjeta ' . ($state['tarjeta'] ?? '?')),
                                ]),
                        ]),
                    ]),

                // ==========================================
                // SECCIÓN 4: TELEVISIÓN (Solo TV)
                // ==========================================
                Forms\Components\Section::make('Estado de Televisión')
                    ->description('Monitoreo de grilla de canales.')
                    ->icon('heroicon-o-tv')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(1)->schema([
                            // Forms\Components\TextInput::make('tv_canales_activos')->label('Canales Activos')->default(90)->required(),
                            Forms\Components\TextInput::make('tv_canales_total')->label('Total Grilla')->default(92)->readOnly(),
                        ]),
                        Forms\Components\TagsInput::make('tv_canales_offline')
                            ->label('Canales Offline')
                            ->suggestions(array_keys(CanalesHelper::getLista()))
                            ->color('danger')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                if (empty($state)) { $set('tv_ticket_existente', false); return; }
                                $existe = Incident::where('tipo_falla', 'falla_tv')->where('estado', '!=', 'resuelto')->get()->contains(fn ($inc) => count(array_intersect($state, $inc->tv_canales_afectados ?? [])) > 0);
                                $set('tv_ticket_existente', $existe);
                                if ($existe) Notification::make()->title('Aviso')->body('Canales con ticket abierto.')->info()->send();
                            }),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('generar_ticket_tv')
                                ->label('Generar Ticket TV')->icon('heroicon-m-ticket')->color('warning')
                                ->form([
                                    Forms\Components\Select::make('responsible_id')
                                        ->label('Asignar Responsable')
                                        ->options(\App\Models\User::all()->pluck('name', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->preload(),
                                ])
                                ->action(function (array $data, Forms\Get $get, Forms\Set $set, $livewire) {
                                    $canales = $get('tv_canales_offline');
                                    $responsibleId = $data['responsible_id'];

                                    if (empty($canales)) { Notification::make()->title('Selecciona canales')->danger()->send(); return; }

                                    if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                                        Notification::make()->title('Ticket en Cola')->body('Se creará al guardar.')->success()->send();
                                        $set('tv_ticket_en_cola', true);
                                        $set('tv_responsible_id', $responsibleId);
                                    } else {
                                        $reporte = $livewire->record;
                                        $incident = Incident::create([ 'report_id' => $reporte->id, 'tipo_falla' => 'falla_tv', 'tv_canales_afectados' => $canales, 'barrios' => 'General', 'estado' => 'pendiente', 'descripcion' => 'Falla TV Manual.', 'identificador' => 'Falla TV' ]);
                                        
                                        $incident->responsibles()->attach($responsibleId, [
                                            'status' => 'pending',
                                            'assigned_by' => auth()->id(),
                                            'assigned_at' => now(),
                                        ]);

                                        Notification::make()->title('Ticket TV Creado')->success()->send();
                                        $set('tv_ticket_existente', true); 
                                    }
                                }),
                        ])->visible(fn (Forms\Get $get) => !empty($get('tv_canales_offline')) && !$get('tv_ticket_existente') && !$get('tv_ticket_en_cola')),

                        Forms\Components\Placeholder::make('aviso_tv_existe')->label('')->content('✅ Ticket existente.')->visible(fn (Forms\Get $get) => $get('tv_ticket_existente') === true)->extraAttributes(['class' => 'text-green-600 font-bold text-xs bg-green-50 p-2 rounded']),
                        Forms\Components\Placeholder::make('aviso_tv_cola')->label('')->content('🕒 Ticket TV programado.')->visible(fn (Forms\Get $get) => $get('tv_ticket_en_cola') === true)->extraAttributes(['class' => 'text-blue-600 font-bold text-xs bg-blue-50 p-2 rounded']),
                        
                        Forms\Components\Hidden::make('tv_ticket_existente')->default(false)->dehydrated(false),
                        Forms\Components\Hidden::make('tv_ticket_en_cola')->default(false)->dehydrated(false),
                        Forms\Components\Hidden::make('tv_responsible_id'),

                        Forms\Components\Textarea::make('tv_observaciones')->label('Observaciones TV')->rows(2),
                    ]),

                // ==========================================
                // SECCIÓN 5: SERVIDORES Y ENERGÍA (NUEVA SECCIÓN SOLICITADA)
                // ==========================================
                Forms\Components\Section::make('Estado de Servidores y Planta Eléctrica')
                    ->description('Monitoreo de servicios críticos y energía.')
                    ->icon('heroicon-o-bolt')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('lista_servidores')
                            ->label('Lista de Activos')
                            ->schema([
                                Forms\Components\Grid::make(12)->schema([
                                    Forms\Components\TextInput::make('nombre')
                                        ->hiddenLabel()
                                        ->required()
                                        ->readOnly() // Nombres fijos para que no los cambien
                                        ->columnSpan(4),
                                    
                                    Forms\Components\Toggle::make('estado')
                                        ->label('Operativo')
                                        ->default(true)
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->inline(false)
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('detalle')
                                        ->label('Novedad / Observación')
                                        ->placeholder('Sin novedad')
                                        ->columnSpan(6),
                                ])
                            ])
                            ->default([
                                ['nombre' => 'Servidor Enlace', 'estado' => true, 'detalle' => null],
                                ['nombre' => 'Servidor Aginet', 'estado' => true, 'detalle' => null],
                                ['nombre' => 'Servidores de Cámaras', 'estado' => true, 'detalle' => null],
                                ['nombre' => 'Servidor Zabbix', 'estado' => true, 'detalle' => null],
                                ['nombre' => 'Máquinas Virtuales', 'estado' => true, 'detalle' => null],
                                ['nombre' => 'Página Web', 'estado' => true, 'detalle' => null],
                                ['nombre' => 'Planta Eléctrica', 'estado' => true, 'detalle' => null],
                                ['nombre' => 'Intalflix', 'estado' => true, 'detalle' => null],
                            ])
                            ->addable(false) // No permitir agregar más cosas raras
                            ->deletable(false) // No permitir borrar los fijos
                            ->reorderable(false)
                            ->columns(1),

                        // Campo de observaciones generales de servidores (por si acaso)
                        Forms\Components\Textarea::make('novedades_servidores')
                            ->label('Observaciones Adicionales de Servidores')
                            ->placeholder('Ej: Mantenimiento programado para la noche...')
                            ->rows(2),
                    ]),

                // ==========================================
                // SECCIÓN 6: EVIDENCIAS
                // ==========================================
                Forms\Components\Section::make('Evidencias')
                    ->schema([
                        Forms\Components\FileUpload::make('photos')
                            ->label('Fotos del Reporte')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->directory('report-monteria-photos')
                            ->columnSpanFull(),
                    ])->collapsible(),

                // ==========================================
                // SECCIÓN 7: INCIDENTES REPORTADOS
                // ==========================================
                Forms\Components\Section::make('Novedades e Incidentes')
                    ->description('Registro de tickets y fallas generales.')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('incidents')
                            ->relationship()
                            ->label('Listado de Incidentes')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['identificador'] ?? ($state['tipo_falla'] ?? 'Incidente'))
                            ->schema([
                                Forms\Components\Select::make('tipo_falla')->options([
                                    'falla_olt' => '📡 Falla en OLT', 'falla_tv' => '📺 Falla TV',/* 'fibra' => '✂️ Fibra',
                                    'energia' => '⚡ Energía', 
                                    'equipo_alarmado' => '🚨 Equipo', 
                                    'mantenimiento' => '🛠️ Mant.', 
                                    */
                                ])->required()->live()->afterStateUpdated(fn (Forms\Set $set) => $set('identificador', null))->columnSpanFull(),
                                
                                Forms\Components\Group::make()->visible(fn (Forms\Get $get) => $get('tipo_falla') === 'falla_olt')->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Select::make('olt_nombre')->options(['Main'=>'Main','Backup'=>'Backup'])->required(),
                                        Forms\Components\Repeater::make('olt_afectacion')->label('Tarjetas')->schema([
                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\Select::make('tarjeta')->options(array_combine(range(1,17), range(1,17)))->required(),
                                                Forms\Components\Select::make('puertos')->multiple()->options(array_combine(range(1,16), range(1,16)))->required(),
                                            ])
                                        ])->columnSpan(2)->grid(1),
                                    ])
                                ]),
                                Forms\Components\Select::make('tv_canales_afectados')->multiple()->searchable()->options(CanalesHelper::getLista())->visible(fn ($get) => $get('tipo_falla') === 'falla_tv'),
                                Forms\Components\TextInput::make('identificador')->required()->visible(fn ($get) => !in_array($get('tipo_falla'), ['falla_olt', 'falla_tv']))->distinct()->validationAttribute('Equipo')->hint('No duplicar'),
                                Forms\Components\TextInput::make('barrios')->required()->columnSpanFull(),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('usuarios_afectados')->numeric(),
                                    Forms\Components\Select::make('estado')->options(['pendiente'=>'Pendiente','resuelto'=>'Resuelto'])->default('pendiente')->required(),
                                ]),
                                Forms\Components\Textarea::make('descripcion')->rows(2)->columnSpanFull(),
                            ])->columns(2),
                        Forms\Components\Textarea::make('observaciones_generales')->label('Observaciones Generales del Turno')->rows(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Hora')->dateTime('h:i A')->sortable()->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('turno')->badge()->color(fn ($state) => match($state){'mañana'=>'warning','tarde'=>'info',default=>'gray'}),
                Tables\Columns\TextColumn::make('temp_olt_monteria')->label('OLT Main')->suffix('°C'),
                Tables\Columns\TextColumn::make('incidents_count')->label('Tickets')->counts('incidents')->badge()->color('danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
    
    public static function getPages(): array { return ['index' => Pages\ListReports::route('/'), 'create' => Pages\CreateReport::route('/create'), 'edit' => Pages\EditReport::route('/{record}/edit')]; }
    public static function getRelations(): array { return []; }
}
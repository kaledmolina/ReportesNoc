<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportRegionalResource\Pages;
use App\Models\ReportRegional;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportRegionalResource extends Resource
{
    protected static ?string $model = ReportRegional::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Reportes Regionales';
    protected static ?string $modelLabel = 'Reporte Regional';
    protected static ?string $navigationGroup = 'Reportes Regionales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('fecha')->required()->default(now()),
                        Forms\Components\Select::make('turno')
                            ->options(['mañana' => 'Mañana', 'tarde' => 'Tarde', 'noche' => 'Noche'])
                            ->required(),
                        Forms\Components\Hidden::make('user_id')->default(auth()->id()),
                    ]),

                Forms\Components\Tabs::make('Sedes')
                    ->tabs([
                        // --- VALENCIA ---
                        Forms\Components\Tabs\Tab::make('Valencia')
                            ->icon('heroicon-m-building-office')
                            ->schema([
                                self::buildItem('valencia_bgp_2116_operativo', 'detalle_valencia_bgp_2116', 'BGP Mikrotik 2116'),
                                self::buildItem('valencia_olt_swifts_operativa', 'detalle_valencia_olt_swifts', 'OLT Swifts'),
                                self::buildItem('valencia_mikrotik_1036_operativo', 'detalle_valencia_mikrotik_1036', 'Mikrotik 1036'),
                                self::buildItem('valencia_servidor_tv_operativo', 'detalle_valencia_servidor_tv', 'Servidor TV'),
                                self::buildItem('valencia_modulador_ip_operativo', 'detalle_valencia_modulador_ip', 'Modulador IP'),
                                self::buildItem('valencia_servidor_intalflix_operativo', 'detalle_valencia_servidor_intalflix', 'Servidor Intalflix'),
                                self::buildItem('valencia_servidor_vmix_operativo', 'detalle_valencia_servidor_vmix', 'Servidor vMix'),
                                Forms\Components\FileUpload::make('photos_valencia')
                                    ->label('Fotos Valencia')
                                    ->multiple()
                                    ->image()
                                    ->imageEditor()
                                    ->directory('report-regional-valencia')
                                    ->columnSpanFull(),
                            ]),

                        // --- TIERRALTA ---
                        Forms\Components\Tabs\Tab::make('Tierralta')
                            ->icon('heroicon-m-building-office')
                            ->schema([
                                self::buildItem('tierralta_olt_operativa', 'detalle_tierralta_olt', 'OLT'),
                                self::buildItem('tierralta_olt_9_marzo_operativa', 'detalle_tierralta_olt_9_marzo', 'OLT 9 de marzo'),
                                self::buildItem('tierralta_mikrotik_1036_operativo', 'detalle_tierralta_mikrotik_1036', 'Mikrotik 1036'),
                                self::buildItem('tierralta_mikrotik_fomento_operativo', 'detalle_tierralta_mikrotik_fomento', 'Mikrotik Fomento'),
                                self::buildItem('tierralta_enlace_urra_operativo', 'detalle_tierralta_enlace_urra', 'Enlace Funcionarios Urrá'),
                                self::buildItem('tierralta_enlace_ancla_operativo', 'detalle_tierralta_enlace_ancla', 'Enlace El Ancla'),
                                Forms\Components\FileUpload::make('photos_tierralta')
                                    ->label('Fotos Tierralta')
                                    ->multiple()
                                    ->image()
                                    ->imageEditor()
                                    ->directory('report-regional-tierralta')
                                    ->columnSpanFull(),
                            ]),

                        // --- SAN PEDRO ---
                        Forms\Components\Tabs\Tab::make('San Pedro')
                            ->icon('heroicon-m-building-office')
                            ->schema([
                                self::buildItem('san_pedro_olt_operativa', 'detalle_san_pedro_olt', 'OLT'),
                                self::buildItem('san_pedro_mikrotik_1036_operativo', 'detalle_san_pedro_mikrotik_1036', 'Mikrotik 1036'),
                                Forms\Components\FileUpload::make('photos_san_pedro')
                                    ->label('Fotos San Pedro')
                                    ->multiple()
                                    ->image()
                                    ->imageEditor()
                                    ->directory('report-regional-san-pedro')
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpanFull(),


                Forms\Components\Textarea::make('observaciones_generales')
                    ->label('Observaciones Generales')
                    ->columnSpanFull(),
            ]);
    }

    // Helper para crear los campos repetitivos
    protected static function buildItem($toggleField, $textField, $label)
    {
        return Forms\Components\Group::make()->schema([
            Forms\Components\Toggle::make($toggleField)
                ->label($label)
                ->default(true)
                ->onColor('success')
                ->offColor('danger')
                ->inline(false),
            Forms\Components\TextInput::make($textField)
                ->label('Novedad ' . $label)
                ->placeholder('Opcional')
                ->columnSpan(2),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('turno')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('h:i A')->label('Creado'),
                Tables\Columns\TextColumn::make('incidents_count')->counts('incidents')->label('Incidentes')->badge()->color('danger'),
            ])
            ->defaultSort('fecha', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportRegionals::route('/'),
            'create' => Pages\CreateReportRegional::route('/create'),
            'edit' => Pages\EditReportRegional::route('/{record}/edit'),
        ];
    }
}

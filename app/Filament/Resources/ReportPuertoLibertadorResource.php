<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportPuertoLibertadorResource\Pages;
use App\Models\ReportPuertoLibertador;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReportPuertoLibertadorResource extends Resource
{
    protected static ?string $model = ReportPuertoLibertador::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Reportes Puerto Libertador';
    protected static ?string $modelLabel = 'Reporte Puerto Libertador';
    protected static ?string $navigationGroup = 'Reportes Puerto Libertador';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('turno')
                            ->options([
                                'mañana' => 'Mañana',
                                'tarde' => 'Tarde',
                                'noche' => 'Noche',
                            ])
                            ->required(),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\Hidden::make('ciudad')
                            ->default('Puerto Libertador'),
                    ])->columns(2),

                Forms\Components\Section::make('Infraestructura y Equipos')
                    ->schema([
                        // OLT
                        Forms\Components\Group::make()->schema([
                            Forms\Components\Toggle::make('olt_operativa')
                                ->label('OLT Operativa')
                                ->default(true)
                                ->onColor('success')
                                ->offColor('danger')
                                ->inline(false),
                            Forms\Components\TextInput::make('detalle_olt')
                                ->label('Novedad OLT')
                                ->placeholder('Opcional')
                                ->columnSpan(2),
                        ])->columns(3),

                        // Mikrotik
                        Forms\Components\Group::make()->schema([
                            Forms\Components\Toggle::make('mikrotik_2116_operativo')
                                ->label('Mikrotik 2116')
                                ->default(true)
                                ->onColor('success')
                                ->offColor('danger')
                                ->inline(false),
                            Forms\Components\TextInput::make('detalle_mikrotik')
                                ->label('Novedad Mikrotik')
                                ->placeholder('Opcional')
                                ->columnSpan(2),
                        ])->columns(3),

                        // Enlace
                        Forms\Components\Group::make()->schema([
                            Forms\Components\Toggle::make('enlace_dedicado_operativo')
                                ->label('Enlace Dedicado')
                                ->default(true)
                                ->onColor('success')
                                ->offColor('danger')
                                ->inline(false),
                            Forms\Components\TextInput::make('detalle_enlace')
                                ->label('Novedad Enlace')
                                ->placeholder('Opcional')
                                ->columnSpan(2),
                        ])->columns(3),

                        // Servidor TV
                        Forms\Components\Group::make()->schema([
                            Forms\Components\Toggle::make('servidor_tv_operativo')
                                ->label('Servidor TV')
                                ->default(true)
                                ->onColor('success')
                                ->offColor('danger')
                                ->inline(false),
                            Forms\Components\TextInput::make('detalle_tv')
                                ->label('Novedad TV')
                                ->placeholder('Opcional')
                                ->columnSpan(2),
                        ])->columns(3),

                        // Modulador
                        Forms\Components\Group::make()->schema([
                            Forms\Components\Toggle::make('modulador_ip_operativo')
                                ->label('Modulador IP')
                                ->default(true)
                                ->onColor('success')
                                ->offColor('danger')
                                ->inline(false),
                            Forms\Components\TextInput::make('detalle_modulador')
                                ->label('Novedad Modulador')
                                ->placeholder('Opcional')
                                ->columnSpan(2),
                        ])->columns(3),
                    ]),

                Forms\Components\Section::make('Televisión')
                    ->schema([
                        Forms\Components\TextInput::make('tv_canales_activos')
                            ->numeric()
                            ->label('Canales Activos'),
                        Forms\Components\TextInput::make('tv_canales_total')
                            ->numeric()
                            ->default(92)
                            ->label('Total Canales'),
                        // Aquí podrías agregar un repeater o tags input para canales offline si es necesario
                    ])->columns(2),

                Forms\Components\Section::make('Evidencias')
                    ->schema([
                        Forms\Components\FileUpload::make('photos')
                            ->label('Fotos del Reporte')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->directory('report-puerto-photos')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Observaciones')
                    ->schema([
                        Forms\Components\Textarea::make('observaciones_generales')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('turno')->badge(),
                Tables\Columns\IconColumn::make('olt_operativa')->boolean()->label('OLT'),
                Tables\Columns\IconColumn::make('mikrotik_2116_operativo')->boolean()->label('Mikrotik'),
                Tables\Columns\IconColumn::make('enlace_dedicado_operativo')->boolean()->label('Enlace'),
                Tables\Columns\IconColumn::make('servidor_tv_operativo')->boolean()->label('TV Server'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListReportPuertoLibertadors::route('/'),
            'create' => Pages\CreateReportPuertoLibertador::route('/create'),
            'edit' => Pages\EditReportPuertoLibertador::route('/{record}/edit'),
        ];
    }
}

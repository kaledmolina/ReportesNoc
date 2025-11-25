<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportPuertoLibertadorHistoryResource\Pages;
use App\Models\ReportPuertoLibertador;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReportPuertoLibertadorHistoryResource extends Resource
{
    protected static ?string $model = ReportPuertoLibertador::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Historial y Exportar';
    protected static ?string $modelLabel = 'Historial Puerto Libertador';
    protected static ?string $slug = 'historial-reportes-puerto-libertador';
    protected static ?string $navigationGroup = 'Reportes Puerto Libertador';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return \App\Filament\Resources\ReportPuertoLibertadorResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable()->label('Fecha'),
                Tables\Columns\TextColumn::make('turno')->badge()->colors(['warning' => 'mañana', 'info' => 'tarde', 'gray' => 'noche']),
                
                Tables\Columns\IconColumn::make('olt_operativa')->boolean()->label('OLT'),
                Tables\Columns\IconColumn::make('mikrotik_2116_operativo')->boolean()->label('Mikrotik'),
                Tables\Columns\IconColumn::make('enlace_dedicado_operativo')->boolean()->label('Enlace'),
                Tables\Columns\IconColumn::make('servidor_tv_operativo')->boolean()->label('TV'),
                
                Tables\Columns\TextColumn::make('incidents_count')
                    ->counts('incidents')
                    ->label('Novedades')
                    ->badge()
                    ->color('danger'),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                Tables\Filters\Filter::make('rango_fechas')
                    ->form([
                        Forms\Components\DatePicker::make('desde')->label('Desde Fecha'),
                        Forms\Components\DatePicker::make('hasta')->label('Hasta Fecha'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['desde'], fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
                            ->when($data['hasta'], fn ($q, $d) => $q->whereDate('fecha', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('exportar_excel')
                        ->label('Descargar Excel (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $csvData = [];
                            $csvData[] = ['ID', 'Fecha', 'Turno', 'OLT OK', 'Mikrotik OK', 'Enlace OK', 'TV OK', 'Modulador OK', 'Observaciones'];

                            foreach ($records as $record) {
                                $csvData[] = [
                                    $record->id,
                                    $record->fecha->format('d/m/Y'),
                                    ucfirst($record->turno),
                                    $record->olt_operativa ? 'SI' : 'NO',
                                    $record->mikrotik_2116_operativo ? 'SI' : 'NO',
                                    $record->enlace_dedicado_operativo ? 'SI' : 'NO',
                                    $record->servidor_tv_operativo ? 'SI' : 'NO',
                                    $record->modulador_ip_operativo ? 'SI' : 'NO',
                                    $record->observaciones_generales,
                                ];
                            }

                            $callback = function () use ($csvData) {
                                $file = fopen('php://output', 'w');
                                fputs($file, "\xEF\xBB\xBF"); 
                                foreach ($csvData as $row) fputcsv($file, $row, ';');
                                fclose($file);
                            };

                            return response()->stream($callback, 200, [
                                "Content-type" => "text/csv",
                                "Content-Disposition" => "attachment; filename=Reportes_PL_" . now()->format('Y-m-d_His') . ".csv",
                                "Pragma" => "no-cache",
                                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                                "Expires" => "0"
                            ]);
                        })
                        ->deselectRecordsAfterCompletion()
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportPuertoLibertadorHistories::route('/'),
        ];
    }
}

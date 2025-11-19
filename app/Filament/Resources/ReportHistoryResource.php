<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportHistoryResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;

class ReportHistoryResource extends Resource
{
    // Apuntamos al mismo modelo 'Report'
    protected static ?string $model = Report::class;

    // Icono diferente para distinguirlo en el menú
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Historial y Exportar';
    protected static ?string $modelLabel = 'Historial';
    protected static ?string $slug = 'historial-reportes';
    
    // Orden en el menú (debajo del reporte diario)
    protected static ?int $navigationSort = 2;

    // Deshabilitamos la creación desde aquí (es solo histórico)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        // Solo lectura, usamos el mismo esquema básico por si quieren ver detalle
        return \App\Filament\Resources\ReportResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('turno')
                    ->badge()
                    ->colors([
                        'warning' => 'mañana',
                        'info' => 'tarde',
                        'gray' => 'noche',
                    ]),

                Tables\Columns\TextColumn::make('temp_olt_monteria')
                    ->label('OLT Main')
                    ->suffix('°C'),
                
                Tables\Columns\TextColumn::make('temp_olt_backup')
                    ->label('OLT Backup')
                    ->suffix('°C'),

                Tables\Columns\TextColumn::make('tv_canales_activos')
                    ->label('TV Disp.')
                    ->formatStateUsing(fn ($state, Report $record) => "{$state}/{$record->tv_canales_total}"),

                Tables\Columns\IconColumn::make('intalflix_online')
                    ->label('Intalflix')
                    ->boolean(),

                Tables\Columns\TextColumn::make('incidents_count')
                    ->counts('incidents')
                    ->label('Novedades')
                    ->badge()
                    ->color('danger'),
            ])
            ->defaultSort('fecha', 'desc')
            ->filters([
                // --- FILTRO DE RANGO DE FECHAS ---
                Tables\Filters\Filter::make('rango_fechas')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde Fecha')
                            ->native(false),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta Fecha')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['desde'] ?? null) {
                            $indicators[] = 'Desde: ' . \Carbon\Carbon::parse($data['desde'])->toFormattedDateString();
                        }
                        if ($data['hasta'] ?? null) {
                            $indicators[] = 'Hasta: ' . \Carbon\Carbon::parse($data['hasta'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                // Acción simple para ver el reporte
                Tables\Actions\ViewAction::make(), 
            ])
            ->bulkActions([
                // --- ACCIÓN DE EXPORTAR A EXCEL (CSV) ---
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('exportar_excel')
                        ->label('Descargar Excel (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            // Lógica para generar el CSV
                            $csvData = [];
                            
                            // Cabeceras del Excel
                            $csvData[] = [
                                'ID', 'Fecha', 'Turno', 'Ciudad', 
                                'Temp OLT Main', 'Temp OLT Backup', 
                                'Concentradores OK', 'Proveedores OK',
                                'Canales Activos', 'Total Canales', 'Canales Offline',
                                'Intalflix', 'Resumen Novedades', '# Incidentes'
                            ];

                            // Datos
                            foreach ($records as $record) {
                                $csvData[] = [
                                    $record->id,
                                    $record->fecha->format('d/m/Y'),
                                    ucfirst($record->turno),
                                    $record->ciudad,
                                    $record->temp_olt_monteria,
                                    $record->temp_olt_backup,
                                    $record->concentradores_ok ? 'SI' : 'NO',
                                    $record->proveedores_ok ? 'SI' : 'NO',
                                    $record->tv_canales_activos,
                                    $record->tv_canales_total,
                                    implode(', ', $record->tv_canales_offline ?? []),
                                    $record->intalflix_online ? 'ONLINE' : 'OFFLINE',
                                    $record->observaciones_generales,
                                    $record->incidents_count ?? $record->incidents()->count(),
                                ];
                            }

                            // Generar descarga stream
                            $callback = function () use ($csvData) {
                                $file = fopen('php://output', 'w');
                                // Agregar BOM para que Excel lea bien tildes y caracteres latinos
                                fputs($file, "\xEF\xBB\xBF"); 
                                foreach ($csvData as $row) {
                                    fputcsv($file, $row, ';'); // Usamos punto y coma para Excel en español
                                }
                                fclose($file);
                            };

                            $filename = 'Reportes_NOC_' . now()->format('Y-m-d_His') . '.csv';

                            return response()->stream($callback, 200, [
                                "Content-type" => "text/csv",
                                "Content-Disposition" => "attachment; filename=$filename",
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
            'index' => Pages\ListReportHistories::route('/'),
            // Quitamos create y edit para mantenerlo limpio
        ];
    }
}
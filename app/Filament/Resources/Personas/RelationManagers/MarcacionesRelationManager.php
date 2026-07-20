<?php

namespace App\Filament\Resources\Personas\RelationManagers;

use App\Models\Sia\Asistencia;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Marcaciones del funcionario, embebidas en su ficha de detalle.
 *
 * Mismo criterio de solo lectura que MarcacionesTable: el panel nunca
 * escribe sobre la base del SIA, por eso no hay acciones de crear/editar/
 * eliminar, solo el filtro de rango de fechas y tipo.
 */
class MarcacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'marcaciones';

    protected static ?string $title = 'Marcaciones';

    /**
     * La tabla Asistencia tiene clave primaria compuesta (IdPersona, Fecha,
     * Hora), así que la clave de fila se arma con esos tres campos.
     */
    public function getTableRecordKey(Model|array $record): string
    {
        if ($record instanceof Asistencia) {
            return implode('|', [
                trim((string) $record->IdPersona),
                $record->Fecha?->format('Ymd') ?? '',
                $record->Hora?->format('His') ?? '',
            ]);
        }

        return parent::getTableRecordKey($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Fecha')
                    ->label('Fecha')
                    ->date('d/m/Y'),
                TextColumn::make('Hora')
                    ->label('Hora')
                    ->time('H:i:s'),
                TextColumn::make('Tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match (trim($state)) {
                        Asistencia::TIPO_RELOJ => 'success',
                        Asistencia::TIPO_MANUAL => 'warning',
                        default => 'info',
                    }),
            ])
            ->filters([
                Filter::make('rango')
                    ->schema([
                        DatePicker::make('desde')
                            ->label('Desde')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('hasta')
                            ->label('Hasta')
                            ->default(now()),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $subQuery, string $desde) => $subQuery->whereDate('Fecha', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $subQuery, string $hasta) => $subQuery->whereDate('Fecha', '<=', $hasta))),
                SelectFilter::make('Tipo')
                    ->label('Tipo')
                    ->options([
                        Asistencia::TIPO_RELOJ => 'R',
                        Asistencia::TIPO_A => 'A',
                        Asistencia::TIPO_MANUAL => 'M',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            // La tabla no tiene clave primaria simple: sin esto Filament intenta
            // ordenar por una PK inexistente.
            ->defaultKeySort(false)
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('Fecha')->orderByDesc('Hora'))
            ->defaultPaginationPageOption(25)
            ->paginated([25, 50, 100])
            ->emptyStateHeading('Sin marcaciones en el rango seleccionado');
    }
}

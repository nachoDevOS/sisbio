<?php

namespace App\Filament\Resources\Marcaciones\Tables;

use App\Models\Sia\Asistencia;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarcacionesTable
{
    /**
     * Tabla del listado de marcaciones del SIA.
     *
     * La base remota tiene 4.4 millones de marcaciones en un SQL Server 2008,
     * pero está indexada por fecha: un listado sin rango no es lento (probado:
     * TOP 25 sin WHERE ~0.24s, COUNT(*) completo ~0.07s). El rango por defecto
     * (mes actual) es solo para no arrancar mostrando fechas basura del SIA
     * (años 2064/2103); Desde/Hasta se pueden vaciar para ver todo el historial.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('persona'))
            ->columns([
                TextColumn::make('IdPersona')
                    ->label('CI')
                    ->formatStateUsing(fn (string $state): string => trim($state)),
                TextColumn::make('persona_nombre')
                    ->label('Funcionario')
                    ->state(fn (Asistencia $record): string => $record->persona?->nombre_completo ?? '—'),
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
                // Un solo filtro: rango de fecha + búsqueda por CI o nombre,
                // en vez del buscador global aparte y una caja de fecha aparte.
                // El SIA arrastra marcaciones con fechas basura (años 2064/2103):
                // el tope "hasta hoy" por defecto las deja fuera del listado.
                Filter::make('rango')
                    ->schema([
                        DatePicker::make('desde')
                            ->label('Desde')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('hasta')
                            ->label('Hasta')
                            ->default(now()),
                        TextInput::make('buscar')
                            ->label('Buscar')
                            ->placeholder('CI o nombre…'),
                    ])
                    ->columns(3)
                    ->columnSpan(3)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $subQuery, string $desde) => $subQuery->whereDate('Fecha', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $subQuery, string $hasta) => $subQuery->whereDate('Fecha', '<=', $hasta))
                        ->when($data['buscar'] ?? null, fn (Builder $subQuery, string $buscar) => $subQuery
                            ->where(fn (Builder $condiciones) => $condiciones
                                ->where('IdPersona', 'like', "%{$buscar}%")
                                ->orWhereHas('persona', fn (Builder $subCondiciones) => $subCondiciones
                                    ->where(fn (Builder $nombreCondiciones) => $nombreCondiciones
                                        ->where('Nombres', 'like', "%{$buscar}%")
                                        ->orWhere('Paterno', 'like', "%{$buscar}%")
                                        ->orWhere('Materno', 'like', "%{$buscar}%")))))),
                SelectFilter::make('Tipo')
                    ->label('Tipo')
                    ->options([
                        Asistencia::TIPO_RELOJ => 'R',
                        Asistencia::TIPO_A => 'A',
                        Asistencia::TIPO_MANUAL => 'M',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            // La tabla no tiene clave primaria simple: sin esto Filament intenta
            // ordenar por una PK inexistente.
            ->defaultKeySort(false)
            // Orden fijo, no elegible por el usuario (columnas sin ->sortable()):
            // más reciente primero. Hora como desempate para que la paginación
            // sea determinista entre marcaciones del mismo día.
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('Fecha')->orderByDesc('Hora'))
            ->defaultPaginationPageOption(25)
            ->paginated([25, 50, 100])
            ->emptyStateHeading('Sin marcaciones en el rango seleccionado');
    }
}

<?php

namespace App\Filament\Resources\Marcaciones\Tables;

use App\Models\Sia\Asistencia;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
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
     * por eso el filtro de fecha arranca en el mes actual: evita listados y
     * conteos sobre la tabla completa.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('persona'))
            ->columns([
                TextColumn::make('IdPersona')
                    ->label('Código')
                    ->formatStateUsing(fn (string $state): string => trim($state))
                    ->searchable(),
                TextColumn::make('persona_nombre')
                    ->label('Funcionario')
                    ->state(fn (Asistencia $record): string => $record->persona?->nombre_completo ?? '—')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('persona', fn (Builder $subQuery) => $subQuery
                            ->where(fn (Builder $condiciones) => $condiciones
                                ->where('Nombres', 'like', "%{$search}%")
                                ->orWhere('Paterno', 'like', "%{$search}%")
                                ->orWhere('Materno', 'like', "%{$search}%")))),
                TextColumn::make('Fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    // Hora acompaña como desempate: el orden queda determinista
                    // y la paginación con ROW_NUMBER() no duplica filas.
                    ->sortable(['Fecha', 'Hora']),
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
                    ])
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
            ])
            // La tabla no tiene clave primaria simple: sin esto Filament intenta
            // ordenar por una PK inexistente. El default como string (no closure)
            // se omite cuando el usuario ya ordena por Fecha; un closure se
            // sumaría al orden del usuario y SQL Server rechaza columnas
            // repetidas en el ORDER BY.
            ->defaultKeySort(false)
            ->defaultSort('Fecha', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([25, 50, 100])
            ->emptyStateHeading('Sin marcaciones en el rango seleccionado');
    }
}

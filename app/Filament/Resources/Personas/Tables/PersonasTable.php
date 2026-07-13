<?php

namespace App\Filament\Resources\Personas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PersonasTable
{
    /**
     * Tabla del listado de funcionarios del SIA (solo lectura).
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('IdPersona')
                    ->label('CI')
                    ->formatStateUsing(fn (string $state): string => trim($state))
                    ->searchable(),
                TextColumn::make('Paterno')
                    ->label('Paterno')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('Materno')
                    ->label('Materno')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('Nombres')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('PinReloj')
                    ->label('PIN reloj')
                    ->placeholder('Sin PIN'),
            ])
            ->defaultSort('Paterno')
            ->defaultPaginationPageOption(25)
            ->paginated([25, 50, 100]);
    }
}

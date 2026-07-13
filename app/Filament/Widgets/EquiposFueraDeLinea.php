<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Equipos biométricos activos sin conexión confirmada: lo primero a revisar.
 * Consulta la base local, sin costo para el servidor SIA.
 */
class EquiposFueraDeLinea extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Equipos fuera de línea')
            ->query(Equipo::query()->where('en_linea', false)->where('activo', true))
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre'),
                TextColumn::make('ip')
                    ->label('IP'),
                TextColumn::make('ubicacion')
                    ->label('Ubicación')
                    ->placeholder('Sin ubicación'),
                TextColumn::make('ultima_sync')
                    ->label('Última sincronización')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca'),
            ])
            ->recordUrl(fn (Equipo $record): string => EquipoResource::getUrl('edit', ['record' => $record]))
            ->paginated(false)
            ->emptyStateHeading('Todos los equipos están en línea')
            ->emptyStateDescription('Ningún equipo activo reporta problemas de conexión.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}

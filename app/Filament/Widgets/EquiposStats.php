<?php

namespace App\Filament\Widgets;

use App\Models\Equipo;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Tarjetas de resumen de los equipos biométricos en el tablero.
 */
class EquiposStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total = Equipo::count();
        $enLinea = Equipo::where('en_linea', true)->count();
        $fueraLinea = Equipo::where('en_linea', false)->count();
        $maestros = Equipo::where('es_master', true)->count();

        return [
            Stat::make('Equipos registrados', $total)
                ->description('Total en el sistema')
                ->descriptionIcon('heroicon-o-cpu-chip')
                ->color('primary'),

            Stat::make('En línea', $enLinea)
                ->description('Conectados en la última prueba')
                ->descriptionIcon('heroicon-o-signal')
                ->color('success'),

            Stat::make('Fuera de línea', $fueraLinea)
                ->description('Sin conexión confirmada')
                ->descriptionIcon('heroicon-o-signal-slash')
                ->color('danger'),

            Stat::make('Equipos maestros', $maestros)
                ->description('Origen de las huellas a replicar')
                ->descriptionIcon('heroicon-o-star')
                ->color('warning'),
        ];
    }
}

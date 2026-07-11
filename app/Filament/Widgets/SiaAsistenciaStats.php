<?php

namespace App\Filament\Widgets;

use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Tarjetas de resumen de la asistencia registrada en el SIA.
 *
 * Las consultas van al SQL Server 2008 remoto, por eso se cachean cinco
 * minutos y se desactiva el polling: el tablero no debe castigar esa base.
 */
class SiaAsistenciaStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        try {
            return $this->statsDesdeSia();
        } catch (Throwable) {
            // Si el SQL Server remoto no responde, el tablero sigue en pie.
            return [
                Stat::make('Asistencia SIA', 'Sin conexión')
                    ->description('No se pudo consultar el servidor SIA')
                    ->descriptionIcon('heroicon-o-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }

    /**
     * @return array<Stat>
     */
    protected function statsDesdeSia(): array
    {
        [$marcacionesHoy, $personasHoy, $marcacionesMes, $funcionarios] = Cache::remember(
            'sia.asistencia.stats',
            now()->addMinutes(5),
            fn (): array => [
                Asistencia::whereDate('Fecha', today())->count(),
                Asistencia::whereDate('Fecha', today())->distinct()->count('IdPersona'),
                // El tope "hasta hoy" excluye las fechas basura futuras del SIA.
                Asistencia::whereDate('Fecha', '>=', now()->startOfMonth())->whereDate('Fecha', '<=', today())->count(),
                Persona::count(),
            ],
        );

        return [
            Stat::make('Marcaciones hoy', number_format($marcacionesHoy, 0, ',', '.'))
                ->description('Registradas en el SIA')
                ->descriptionIcon('heroicon-o-finger-print')
                ->color('success'),

            Stat::make('Personas que marcaron hoy', number_format($personasHoy, 0, ',', '.'))
                ->description('Funcionarios con al menos una marcación')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Marcaciones del mes', number_format($marcacionesMes, 0, ',', '.'))
                ->description('Desde el 1.º del mes actual')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Funcionarios registrados', number_format($funcionarios, 0, ',', '.'))
                ->description('Personas en el SIA')
                ->descriptionIcon('heroicon-o-identification')
                ->color('gray'),
        ];
    }
}

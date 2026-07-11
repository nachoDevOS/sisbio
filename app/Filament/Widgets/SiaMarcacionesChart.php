<?php

namespace App\Filament\Widgets;

use App\Models\Sia\Asistencia;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Marcaciones por día de los últimos 14 días, leídas del SIA.
 *
 * Igual que las tarjetas de resumen: cache de cinco minutos y sin polling
 * para no castigar el SQL Server 2008 remoto.
 */
class SiaMarcacionesChart extends ChartWidget
{
    protected ?string $heading = 'Marcaciones por día (últimos 14 días)';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $dias = collect(range(13, 0))->map(fn (int $offset) => today()->subDays($offset));

        try {
            $totales = Cache::remember(
                'sia.asistencia.grafico',
                now()->addMinutes(5),
                fn (): array => Asistencia::query()
                    ->selectRaw('Fecha, count(*) as total')
                    ->whereDate('Fecha', '>=', $dias->first()->toDateString())
                    ->whereDate('Fecha', '<=', today())
                    ->groupBy('Fecha')
                    ->orderBy('Fecha')
                    ->get()
                    ->mapWithKeys(fn (Asistencia $fila): array => [$fila->Fecha->toDateString() => (int) $fila->total])
                    ->all(),
            );
        } catch (Throwable) {
            // Si el SQL Server remoto no responde, el gráfico queda en cero.
            $totales = [];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Marcaciones',
                    'data' => $dias->map(fn ($dia): int => $totales[$dia->toDateString()] ?? 0)->all(),
                ],
            ],
            'labels' => $dias->map(fn ($dia): string => $dia->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

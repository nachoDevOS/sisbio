<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

/**
 * Escritorio: tablero de inicio con el mismo resumen que el Dashboard de
 * Filament (equipos, equipos fuera de línea, asistencia SIA y su gráfico).
 * Las consultas al SIA (SQL Server 2008 remoto) se cachean 5 minutos, igual
 * que los widgets originales, para no castigar esa base con cada visita.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $equipos = [
            'total' => Equipo::count(),
            'en_linea' => Equipo::where('en_linea', true)->count(),
            'fuera_linea' => Equipo::where('en_linea', false)->count(),
            'maestros' => Equipo::where('es_master', true)->count(),
        ];

        $equiposFueraDeLinea = Equipo::query()
            ->where('en_linea', false)
            ->where('activo', true)
            ->get();

        try {
            $sia = $this->statsSia();
            $sinConexionSia = false;
        } catch (Throwable) {
            $sia = null;
            $sinConexionSia = true;
        }

        $grafico = $this->graficoMarcaciones();

        return view('dashboard.index', compact('equipos', 'equiposFueraDeLinea', 'sia', 'sinConexionSia', 'grafico'));
    }

    /**
     * @return array{marcaciones_hoy: int, personas_hoy: int, marcaciones_mes: int, funcionarios: int}
     */
    private function statsSia(): array
    {
        return Cache::remember(
            'sia.asistencia.stats',
            now()->addMinutes(5),
            fn (): array => [
                'marcaciones_hoy' => Asistencia::whereDate('Fecha', today())->count(),
                'personas_hoy' => Asistencia::whereDate('Fecha', today())->distinct()->count('IdPersona'),
                // El tope "hasta hoy" excluye las fechas basura futuras del SIA.
                'marcaciones_mes' => Asistencia::whereDate('Fecha', '>=', now()->startOfMonth())->whereDate('Fecha', '<=', today())->count(),
                'funcionarios' => Persona::count(),
            ],
        );
    }

    /**
     * Marcaciones por día de los últimos 14 días, para el mini gráfico de
     * barras. Sin JS de terceros: se dibuja con CSS puro en la vista.
     *
     * @return array{dias: list<string>, totales: list<int>}
     */
    private function graficoMarcaciones(): array
    {
        $dias = collect(range(13, 0))->map(fn (int $offset) => today()->subDays($offset));

        try {
            $totalesPorFecha = Cache::remember(
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
            $totalesPorFecha = [];
        }

        return [
            'dias' => $dias->map(fn ($dia): string => $dia->format('d/m'))->all(),
            'totales' => $dias->map(fn ($dia): int => $totalesPorFecha[$dia->toDateString()] ?? 0)->all(),
        ];
    }
}

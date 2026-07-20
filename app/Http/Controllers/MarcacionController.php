<?php

namespace App\Http\Controllers;

use App\Models\Sia\Asistencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Listado de solo lectura de las marcaciones del SIA (SQL Server 2008 remoto).
 *
 * La tabla tiene ~4.4 millones de filas, por eso el rango arranca en el mes
 * actual: nunca se lista ni se cuenta la tabla completa. Solo `index`.
 */
class MarcacionController extends Controller
{
    /**
     * Listado paginado de marcaciones, filtrado por rango de fechas.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Asistencia::class);

        // Por defecto: del 1.º del mes hasta hoy (deja fuera las fechas basura
        // futuras que arrastra el SIA, ej. años 2064/2103).
        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $buscar = trim((string) $request->query('buscar', ''));
        $tipo = $request->query('tipo', '');

        $marcaciones = Asistencia::query()
            ->with('persona')
            ->when($desde, fn (Builder $query, string $d) => $query->whereDate('Fecha', '>=', $d))
            ->when($hasta, fn (Builder $query, string $h) => $query->whereDate('Fecha', '<=', $h))
            ->when($buscar !== '', fn (Builder $query) => $query
                ->where(fn (Builder $condiciones) => $condiciones
                    ->where('IdPersona', 'like', "%{$buscar}%")
                    ->orWhereHas('persona', fn (Builder $subCondiciones) => $subCondiciones
                        ->where(fn (Builder $nombreCondiciones) => $nombreCondiciones
                            ->where('Nombres', 'like', "%{$buscar}%")
                            ->orWhere('Paterno', 'like', "%{$buscar}%")
                            ->orWhere('Materno', 'like', "%{$buscar}%")))))
            ->when($tipo !== '', fn (Builder $query) => $query->where('Tipo', $tipo))
            ->orderByDesc('Fecha')
            ->orderByDesc('Hora')
            ->paginate(25)
            ->withQueryString();

        return view('marcaciones.index', compact('marcaciones', 'desde', 'hasta', 'buscar', 'tipo'));
    }
}

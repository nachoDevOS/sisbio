<?php

namespace App\Http\Controllers;

use App\Models\AsignacionTurno;
use App\Services\ResolutorNombres;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Listado (solo lectura) de los turnos asignados a cada funcionario: la tabla
 * local `asignacion_turnos`, migrada de «AsignacionTurnos» del SIA.
 *
 * El funcionario se cruza por **CI** (la asignación solo guarda la cédula) y el
 * turno por la FK **`turno_id`**; `idTurno` queda como dato histórico del SIA y
 * no se usa para relacionar.
 */
class AsignacionTurnoController extends Controller
{
    /**
     * Situaciones posibles respecto de hoy, para el filtro del listado.
     *
     * @var array<string, string>
     */
    public const SITUACIONES = [
        'vigentes' => 'Vigentes hoy',
        'futuras' => 'Aún no vigentes',
        'vencidas' => 'Vencidas',
    ];

    /**
     * Pantalla del listado (browse): el «shell» con los filtros. La tabla la
     * carga por AJAX contra `list()`.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AsignacionTurno::class);

        $buscar = trim((string) $request->query('buscar', ''));
        $dia = (string) $request->query('dia', '');
        $situacion = $this->situacion($request);
        $porPagina = $this->porPagina($request);

        return view('turnos-asignados.index', compact('buscar', 'dia', 'situacion', 'porPagina'));
    }

    /**
     * Devuelve el parcial de la tabla (filas + paginación) para el AJAX.
     */
    public function list(Request $request, ResolutorNombres $resolutor): View
    {
        $this->authorize('viewAny', AsignacionTurno::class);

        $buscar = trim((string) $request->query('q', ''));
        $dia = (string) $request->query('dia', '');
        $situacion = $this->situacion($request);
        $porPagina = $this->porPagina($request);

        $asignaciones = AsignacionTurno::query()
            ->with('turno')
            ->when($buscar !== '', fn (Builder $query) => $query->buscar($buscar))
            ->when($dia !== '', fn (Builder $query) => $query->whereHas('turno', fn (Builder $turno) => $turno->where('dia', $dia)))
            ->when($situacion === 'vigentes', fn (Builder $query) => $query->vigenteEn(today()))
            ->when($situacion === 'futuras', fn (Builder $query) => $query->whereDate('desde', '>', today()))
            ->when($situacion === 'vencidas', fn (Builder $query) => $query->whereDate('hasta', '<', today()))
            ->orderByDesc('desde')
            ->orderBy('ci')
            ->paginate($porPagina)
            ->withQueryString();

        // La columna «Funcionario» (nombre y cargo) sale de Mamoré y, si el CI
        // no está ahí, de la base local.
        $fichas = $resolutor->fichasPorCi($asignaciones->pluck('ci'));

        return view('turnos-asignados.list', compact('asignaciones', 'fichas', 'buscar'));
    }

    /**
     * Filtro de situación del listado: «todas» (por defecto) o una de
     * {@see self::SITUACIONES}. Un valor desconocido cae en «todas».
     */
    private function situacion(Request $request): string
    {
        $situacion = (string) $request->query('situacion', 'todas');

        return array_key_exists($situacion, self::SITUACIONES) ? $situacion : 'todas';
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLicenciaRequest;
use App\Models\AsignacionTurno;
use App\Models\Licencia;
use App\Models\Persona;
use App\Services\RegistroLicencia;
use App\Services\ResolutorNombres;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Licencias/permisos de personal sobre la base local MySQL, con el flujo del
 * sistema de escritorio: se elige el funcionario, se marcan sus turnos
 * asignados y un rango de fechas, y se anotan las licencias resultantes (una
 * por día y turno). Eliminación lógica, como todo el sistema.
 */
class LicenciaController extends Controller
{
    /**
     * Pantalla del listado (browse): el «shell» con los filtros. La tabla se
     * carga por AJAX contra `list()`.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Licencia::class);

        $busqueda = trim((string) $request->query('q', ''));
        $porPagina = $this->porPagina($request);

        return view('licencias.index', compact('busqueda', 'porPagina'));
    }

    /**
     * Devuelve el parcial de la tabla (filas + paginación) para el AJAX.
     */
    public function list(Request $request, ResolutorNombres $resolutor): View
    {
        $this->authorize('viewAny', Licencia::class);

        $busqueda = trim((string) $request->query('q', ''));
        $porPagina = $this->porPagina($request);

        $licencias = Licencia::query()
            ->with('turno')
            ->when($busqueda !== '', fn (Builder $query) => $query->buscar($busqueda))
            ->orderByDesc('fecha')
            ->orderBy('idTurno')
            ->paginate($porPagina)
            ->withQueryString();

        // La columna «Funcionario» sale de Mamoré y, si el CI no está ahí, de
        // la base local (App\Services\ResolutorNombres).
        $nombres = $resolutor->porCi($licencias->pluck('ci'));

        return view('licencias.list', compact('licencias', 'nombres', 'busqueda'));
    }

    /**
     * Pantalla «Licenciar»: combo de funcionario y, si ya hay uno elegido, la
     * grilla de sus turnos asignados más el formulario del rango.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Licencia::class);

        $persona = $this->ubicarFuncionario((string) $request->query('ci', ''));
        // Por defecto solo los turnos vigentes: un funcionario antiguo arrastra
        // decenas de asignaciones viejas que solo son ruido.
        $incluirVencidos = $request->boolean('vencidos');

        $asignaciones = $persona instanceof Persona
            ? $this->turnosAsignados($persona, $incluirVencidos)
            : collect();

        $vencidos = $persona instanceof Persona
            ? $this->contarVencidos($persona)
            : 0;

        // Si el alta grupal volvió por un error de validación, se rearman las
        // fichas de funcionarios ya elegidos con su nombre.
        $elegidos = collect(old('cis', []))
            ->pipe(fn (Collection $cis): Collection => $cis->isEmpty()
                ? collect()
                : Persona::query()->whereIn('ci', $cis->all())->get())
            ->map(fn (Persona $elegido): array => [
                'id' => trim((string) $elegido->ci),
                'texto' => trim((string) $elegido->ci).' — '.($elegido->nombre_completo ?: 'Sin nombre'),
            ])
            ->values();

        return view('licencias.create', compact('persona', 'asignaciones', 'incluirVencidos', 'vencidos', 'elegidos'));
    }

    /**
     * Búsqueda de funcionarios por CI o nombre para el combo de la pantalla
     * «Licenciar». Devuelve hasta 20 coincidencias como JSON.
     */
    public function buscarFuncionarios(Request $request): JsonResponse
    {
        $this->authorize('create', Licencia::class);

        $q = trim((string) $request->query('q', ''));

        $funcionarios = $q === ''
            ? collect()
            : Persona::query()->buscar($q)->orderBy('paterno')->limit(20)->get();

        return response()->json($funcionarios->map(function (Persona $persona): array {
            $ci = trim((string) $persona->ci);
            $pin = trim((string) $persona->pinReloj);

            return [
                'id' => $ci,
                'texto' => $ci.' — '.($persona->nombre_completo ?: 'Sin nombre').($pin !== '' ? " (PIN {$pin})" : ''),
            ];
        })->values());
    }

    /**
     * Anota las licencias del rango, para un funcionario, varios o todos los que
     * tengan turno dentro del rango. La expansión (un registro por funcionario,
     * día y turno) la hace el servicio; la validación, el Request.
     */
    public function store(StoreLicenciaRequest $request, RegistroLicencia $registro): RedirectResponse
    {
        $datos = $request->validated();

        $modo = $datos['modo'];
        $desde = Carbon::parse($datos['desde'])->startOfDay();
        $hasta = Carbon::parse($datos['hasta'])->startOfDay();
        // Los turnos elegidos a mano solo aplican al alta de un funcionario.
        $elegidas = $modo === 'uno' ? ($datos['asignaciones'] ?? []) : [];

        $cis = match ($modo) {
            'uno' => [trim((string) $datos['ci'])],
            'varios' => array_map(trim(...), $datos['cis']),
            // «Todos» no acota por carnet: el propio rango define a quiénes
            // alcanza (los que tengan un turno vigente en esas fechas).
            default => [],
        };

        $asignacionesPorCi = $this->turnosDelRango($cis, $desde, $hasta, $elegidas);

        if ($asignacionesPorCi->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', $this->motivoSinTurnos($modo, $elegidas));
        }

        $conteo = $registro->anotar($asignacionesPorCi, $desde, $hasta, [
            'tCompleto' => $datos['tCompleto'],
            'goceHaberes' => $datos['goceHaberes'],
            'motivo' => $datos['motivo'],
            'lEntra' => $datos['lEntra'] ?? null,
            'lSale' => $datos['lSale'] ?? null,
            'usuario' => (string) ($request->user()?->name ?? ''),
            'usuarioId' => $request->user()?->id,
        ]);

        if ($conteo['creadas'] === 0) {
            return back()
                ->withInput()
                ->with('error', 'No se anotó ninguna licencia: '.$registro->mensaje($conteo));
        }

        // Con un solo funcionario el listado queda filtrado por su carnet; con
        // varios se muestra completo, porque no hay un filtro que los agrupe.
        return redirect()
            ->route('licencias.index', $modo === 'uno' ? ['q' => $cis[0]] : [])
            ->with('estado', $registro->mensaje($conteo));
    }

    /**
     * Por qué no hubo ningún turno que licenciar, según el alcance pedido.
     *
     * @param  list<int>  $elegidas
     */
    private function motivoSinTurnos(string $modo, array $elegidas): string
    {
        if ($elegidas !== []) {
            return 'Los turnos elegidos no pertenecen a ese funcionario.';
        }

        return $modo === 'todos'
            ? 'Ningún funcionario tiene turnos asignados dentro de ese rango de fechas.'
            : 'El funcionario no tiene ningún turno asignado dentro de ese rango de fechas.';
    }

    /**
     * Elimina (lógicamente) una licencia.
     */
    public function destroy(Licencia $licencia): RedirectResponse
    {
        $this->authorize('delete', $licencia);

        $licencia->delete();

        return back()->with('estado', 'Licencia eliminada.');
    }

    /**
     * Turnos asignados al funcionario para la grilla «Turnos asignados a …»:
     * los vigentes primero y, dentro de cada grupo, por día de la semana y hora
     * de entrada.
     *
     * El orden se resuelve en SQL con un join a `turnos` a propósito: ordenar
     * en memoria con `sortBy([...])` no sirve, porque ahí un closure se toma
     * como comparador de dos argumentos, no como extractor del valor a ordenar.
     *
     * @return Collection<int, AsignacionTurno>
     */
    private function turnosAsignados(Persona $persona, bool $incluirVencidos = false): Collection
    {
        $hoy = now()->startOfDay();

        return AsignacionTurno::query()
            ->select('asignacion_turnos.*')
            ->with('turno')
            ->join('turnos', 'turnos.id', '=', 'asignacion_turnos.turno_id')
            // El join saltea el borrado lógico de turnos: hay que excluirlos a mano.
            ->whereNull('turnos.deleted_at')
            ->where('asignacion_turnos.ci', $persona->ci)
            ->unless($incluirVencidos, fn (Builder $query) => $query->where('asignacion_turnos.hasta', '>=', $hoy))
            ->orderByRaw('CASE WHEN asignacion_turnos.hasta >= ? THEN 0 ELSE 1 END', [$hoy])
            ->orderBy('turnos.dia')
            ->orderBy('turnos.hEntrada')
            ->orderByDesc('asignacion_turnos.hasta')
            ->get();
    }

    /**
     * Turnos a licenciar, agrupados por carnet. Sin selección manual se toman
     * las asignaciones cuya vigencia se solapa con el rango pedido: el rango de
     * fechas ya dice qué días son, marcar además los días de la semana sería
     * pedir el mismo dato dos veces. La selección manual queda para el caso de
     * doble turno en un día.
     *
     * Con `$cis` vacío no se acota por funcionario: es el alcance «todos», donde
     * el rango define a quiénes alcanza. Es una sola consulta para cualquier
     * cantidad de funcionarios (un feriado toca más de 400).
     *
     * @param  list<string>  $cis
     * @param  list<int>  $elegidas
     * @return Collection<string, Collection<int, AsignacionTurno>>
     */
    private function turnosDelRango(array $cis, Carbon $desde, Carbon $hasta, array $elegidas): Collection
    {
        return AsignacionTurno::query()
            ->with('turno')
            ->whereHas('turno')
            ->when($cis !== [], fn (Builder $query) => $query->whereIn('ci', $cis))
            ->when($elegidas !== [], fn (Builder $query) => $query->whereIn('id', $elegidas))
            // Solapamiento de rangos: la asignación sirve si empieza antes de
            // que termine el pedido y termina después de que empiece. Con
            // selección manual no se aplica, para permitir altas retroactivas.
            ->when($elegidas === [], fn (Builder $query) => $query
                ->where('desde', '<=', $hasta->copy()->endOfDay())
                ->where('hasta', '>=', $desde))
            ->get()
            ->groupBy(fn (AsignacionTurno $asignacion): string => trim((string) $asignacion->ci));
    }

    /**
     * Cuántas asignaciones vencidas tiene el funcionario, para ofrecer verlas
     * sin cargarlas de entrada.
     */
    private function contarVencidos(Persona $persona): int
    {
        return AsignacionTurno::query()
            ->join('turnos', 'turnos.id', '=', 'asignacion_turnos.turno_id')
            ->whereNull('turnos.deleted_at')
            ->where('asignacion_turnos.ci', $persona->ci)
            ->where('asignacion_turnos.hasta', '<', now()->startOfDay())
            ->count();
    }

    /**
     * Ubica al funcionario por su CI. El `ci` local ya viene sin relleno, así
     * que basta la comparación exacta. Devuelve null si viene vacío o no existe.
     */
    private function ubicarFuncionario(string $ci): ?Persona
    {
        $ci = trim($ci);

        return $ci === '' ? null : Persona::query()->where('ci', $ci)->first();
    }
}

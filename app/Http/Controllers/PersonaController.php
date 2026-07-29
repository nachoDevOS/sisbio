<?php

namespace App\Http\Controllers;

use App\Exceptions\MamoreException;
use App\Models\AsignacionTurno;
use App\Models\Asistencia;
use App\Models\Licencia;
use App\Models\Persona;
use App\Services\DirectorioMamore;
use App\Services\MamoreClient;
use App\Services\ResolutorNombres;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginador;
use Illuminate\View\View;

/**
 * Consulta (solo lectura) de los funcionarios. El listado tiene dos fuentes,
 * elegibles con un select: «Mamoré» (API externa de Datos Personales, por
 * defecto) y «SIAT» (base local MySQL, tabla `personas`). El alta/edición/
 * borrado siguen siendo de los sistemas de origen.
 */
class PersonaController extends Controller
{
    /**
     * Pantalla del listado (browse): el «shell» con los filtros. La tabla en sí
     * se carga por AJAX contra `list()`, con su propia paginación.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Persona::class);

        return view('funcionarios.index');
    }

    /**
     * Devuelve el parcial de la tabla (filas + paginación) para el AJAX del
     * listado, con búsqueda por CI o nombre desde la fuente elegida (Mamoré por
     * defecto, o SIAT local).
     */
    public function list(Request $request, MamoreClient $mamore): View
    {
        $this->authorize('viewAny', Persona::class);

        $busqueda = trim((string) $request->query('q', ''));
        $porPagina = $this->porPagina($request);
        $fuente = $request->query('fuente') === 'siat' ? 'siat' : 'mamore';
        $contrato = $this->contrato($request);
        $errorFuente = null;
        $totales = ['con' => null, 'sin' => null];

        if ($fuente === 'siat') {
            // SIAT no conoce los contratos: el filtro es solo de Mamoré.
            $contrato = 'todos';
            $funcionarios = $this->funcionariosLocales($busqueda, $porPagina);
        } else {
            [$funcionarios, $errorFuente, $totales] = $this->funcionariosMamore($request, $mamore, $busqueda, $porPagina, $contrato);
        }

        return view('funcionarios.list', compact('funcionarios', 'fuente', 'contrato', 'totales', 'errorFuente'));
    }

    /**
     * Ficha de detalle de un funcionario local (SIAT). Marcaciones, licencias y
     * turnos no van en esta respuesta: son las tres solapas del pie de la
     * ficha, y cada una carga su tabla por AJAX cuando se la abre.
     */
    public function show(Request $request, Persona $persona): View
    {
        $this->authorize('view', $persona);

        $persona->loadMissing('profesion');

        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $tipo = $request->query('tipo', '');

        return view('funcionarios.show', compact('persona', 'desde', 'hasta', 'tipo'));
    }

    /**
     * Devuelve el parcial de las marcaciones de una cédula (tabla +
     * paginación) para el AJAX del panel de la ficha. Sirve tanto a la ficha
     * local como a la de Mamoré: `asistencias` se cruza por CI, así que no
     * hace falta que la persona exista en la base local.
     */
    public function marcacionesList(Request $request): View
    {
        $this->authorize('viewAny', Persona::class);

        $ci = trim((string) $request->query('ci', ''));
        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $tipo = $request->query('tipo', '');

        $marcaciones = Asistencia::query()
            ->where('ci', $ci)
            ->when($desde, fn (Builder $query, string $d) => $query->whereDate('fecha', '>=', $d))
            ->when($hasta, fn (Builder $query, string $h) => $query->whereDate('fecha', '<=', $h))
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->paginate($this->porPagina($request))
            ->withQueryString();

        return view('funcionarios.marcaciones-list', compact('marcaciones'));
    }

    /**
     * Parcial de las licencias de una cédula para la solapa de la ficha.
     * También se cruza por CI, así que sirve a las dos fichas.
     */
    public function licenciasList(Request $request): View
    {
        $this->authorize('viewAny', Licencia::class);

        $ci = trim((string) $request->query('ci', ''));

        $licencias = Licencia::query()
            ->with('turno')
            ->where('ci', $ci)
            ->orderByDesc('fecha')
            ->paginate($this->porPagina($request))
            ->withQueryString();

        return view('funcionarios.licencias-list', compact('licencias'));
    }

    /**
     * Parcial de los turnos asignados de una cédula para la solapa de la ficha.
     *
     * Por defecto salen todos, con los que siguen en pie primero; el filtro
     * permite quedarse solo con los vigentes o mirar solo los vencidos.
     */
    public function turnosList(Request $request): View
    {
        $this->authorize('viewAny', AsignacionTurno::class);

        $ci = trim((string) $request->query('ci', ''));
        $situacion = $this->situacionTurnos($request);
        // De qué ficha se pidió la tabla: los botones de la fila lo devuelven
        // al concluir un turno, para volver a esta misma solapa.
        $origenFicha = in_array($request->query('origen'), ['local', 'mamore'], true)
            ? (string) $request->query('origen')
            : '';
        // El modal de licencia muestra esta misma tabla solo como referencia:
        // pide `acciones=0` para que no aparezcan concluir ni eliminar.
        $conAcciones = $request->boolean('acciones', true);

        $asignaciones = AsignacionTurno::query()
            ->delFuncionario($ci, incluirVencidas: $situacion !== 'vigentes')
            ->when($situacion === 'vencidas', fn (Builder $query) => $query->whereDate('asignacion_turnos.hasta', '<', today()))
            ->paginate($this->porPagina($request))
            ->withQueryString();

        return view('funcionarios.turnos-list', compact('asignaciones', 'origenFicha', 'conAcciones'));
    }

    /**
     * Filtro de la solapa de turnos: «todas» (por defecto), «vigentes» o
     * «vencidas». Un valor desconocido cae en «todas».
     */
    private function situacionTurnos(Request $request): string
    {
        $situacion = (string) $request->query('situacion', 'todas');

        return in_array($situacion, ['vigentes', 'vencidas'], true) ? $situacion : 'todas';
    }

    /**
     * Ficha de solo lectura de una persona de la API de Mamoré (por cédula).
     * Sus marcaciones las carga el panel por AJAX contra `marcacionesList()`.
     */
    public function mamoreShow(Request $request, string $ci, MamoreClient $mamore): View
    {
        $this->authorize('viewAny', Persona::class);

        try {
            $persona = $mamore->personByCi($ci);
        } catch (MamoreException $e) {
            abort(502, $e->getMessage());
        }

        abort_if($persona === null, 404);

        $ciMarcaciones = trim((string) ($persona['ci'] ?? $ci));
        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $tipo = $request->query('tipo', '');

        // El reporte imprimible cuelga del funcionario local: solo se ofrece si
        // esta cédula también está en la base local.
        $personaLocal = Persona::query()->where('ci', $ciMarcaciones)->first();

        return view('funcionarios.mamore-show', compact('persona', 'personaLocal', 'desde', 'hasta', 'tipo'));
    }

    /**
     * Reporte imprimible de las marcaciones «sin procesar» del funcionario:
     * todas las marcaciones crudas del rango (sin paginar, en orden
     * cronológico), con el formato del sistema de escritorio viejo.
     */
    public function reporteMarcaciones(Request $request, Persona $persona, ResolutorNombres $resolutor): View
    {
        $this->authorize('view', $persona);

        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $tipo = $request->query('tipo', '');

        $marcaciones = $persona->marcaciones()
            ->when($desde, fn (Builder $query, string $d) => $query->whereDate('fecha', '>=', $d))
            ->when($hasta, fn (Builder $query, string $h) => $query->whereDate('fecha', '<=', $h))
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();

        // El reporte se imprime con la ficha resuelta (Mamoré primero, para que
        // salga el cargo), con la base local como respaldo.
        $ficha = $resolutor->fichaPorCi((string) $persona->ci) ?? [
            'ci' => trim((string) $persona->ci),
            'nombre' => $persona->nombre_completo ?: '—',
            'nombreFormal' => collect([$persona->paterno, $persona->materno, $persona->nombres])
                ->map(fn ($parte): string => trim((string) $parte))
                ->filter()
                ->implode(' ') ?: '—',
            'cargo' => null,
            'direccion' => null,
            'pinReloj' => trim((string) $persona->pinReloj),
        ];

        return view('reportes.marcaciones.sinProcesar.print', [
            'persona' => $ficha,
            'marcaciones' => $marcaciones,
            'desde' => $desde,
            'hasta' => $hasta,
            'tipo' => $tipo,
        ]);
    }

    /**
     * Filtro de contrato del listado: «todos» (por defecto), «con» o «sin».
     * Cualquier otro valor cae en «todos» para no dejar la tabla vacía.
     */
    private function contrato(Request $request): string
    {
        $contrato = (string) $request->query('contrato', 'todos');

        return in_array($contrato, ['con', 'sin'], true) ? $contrato : 'todos';
    }

    /**
     * Funcionarios de la base local (SIAT), normalizados a la forma común de la
     * tabla.
     */
    private function funcionariosLocales(string $busqueda, int $porPagina): LengthAwarePaginator
    {
        return Persona::query()
            ->with('profesion')
            ->when($busqueda !== '', fn (Builder $query) => $query->buscar($busqueda))
            ->orderBy('paterno')
            ->paginate($porPagina)
            ->withQueryString()
            ->through(fn (Persona $persona): array => [
                'id' => $persona->id,
                'ci' => trim((string) $persona->ci),
                'nombre' => $persona->nombre_completo ?: '—',
                'profesion' => trim((string) $persona->profesion?->nombreProfesion),
                'pinReloj' => trim((string) $persona->pinReloj),
                'nacimiento' => $persona->fechaNacimiento?->format('d/m/Y'),
                'edad' => $persona->fechaNacimiento?->age,
                'ver' => route('funcionarios.show', $persona),
                // SIAT no tiene contratos: las columnas de Mamoré van vacías.
                'cargo' => null,
                'direccion' => null,
                'conContrato' => null,
            ]);
    }

    /**
     * Funcionarios de la API de Mamoré, normalizados a la forma común de la
     * tabla. Si la API falla, devuelve un paginador vacío y el mensaje de error.
     *
     * La fuente es siempre `/people`: devuelve todas las personas y cada una
     * trae su contrato firmado (de donde salen el cargo y la dirección) o `null`.
     * El filtro por situación de contrato lo resuelve la propia API.
     *
     * La API busca por un solo término (no cruza nombre + apellido). Para
     * buscar por varias palabras, se trae un lote por el término más largo y se
     * filtra localmente por todas las palabras.
     *
     * @return array{0: LengthAwarePaginator, 1: ?string, 2: array{con: ?int, sin: ?int}}
     */
    private function funcionariosMamore(Request $request, MamoreClient $mamore, string $busqueda, int $porPagina, string $contrato): array
    {
        $terminos = preg_split('/\s+/', trim($busqueda), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $pagina = max(1, (int) $request->query('page', 1));

        try {
            if (count($terminos) <= 1) {
                $respuesta = $mamore->people($pagina, $porPagina, $busqueda, $contrato);
                $meta = $respuesta['meta'] ?? [];

                $paginador = new Paginador(
                    $this->normalizarMamore($respuesta['data'] ?? []),
                    (int) ($meta['total'] ?? count($respuesta['data'] ?? [])),
                    (int) ($meta['per_page'] ?? $porPagina),
                    (int) ($meta['current_page'] ?? $pagina),
                    ['path' => $request->url(), 'query' => $request->query()],
                );

                return [$paginador, null, $this->totalesPorContrato($meta)];
            }

            $terminoMasLargo = collect($terminos)->sortByDesc(fn (string $t): int => mb_strlen($t))->first();
            $respuesta = $mamore->people(1, 100, (string) $terminoMasLargo, $contrato);

            $filtrados = collect($this->normalizarMamore($respuesta['data'] ?? []))
                ->filter(function (array $fila) use ($terminos): bool {
                    $heno = mb_strtolower($fila['nombre'].' '.$fila['ci']);

                    foreach ($terminos as $termino) {
                        if (! str_contains($heno, mb_strtolower($termino))) {
                            return false;
                        }
                    }

                    return true;
                })
                ->values();

            $paginador = new Paginador(
                $filtrados->forPage($pagina, $porPagina)->values()->all(),
                $filtrados->count(),
                $porPagina,
                $pagina,
                ['path' => $request->url(), 'query' => $request->query()],
            );

            // Con búsqueda de varias palabras el filtrado es local, así que los
            // totales de la API no corresponden a lo que se está mostrando.
            return [$paginador, null, ['con' => null, 'sin' => null]];
        } catch (MamoreException $e) {
            return [$this->paginadorVacio($request, $porPagina), $e->getMessage(), ['con' => null, 'sin' => null]];
        }
    }

    /**
     * Totales de cada situación de contrato para etiquetar el select. La API los
     * entrega en su `meta`, ya con la búsqueda aplicada.
     *
     * @param  array<string, mixed>  $meta
     * @return array{con: ?int, sin: ?int}
     */
    private function totalesPorContrato(array $meta): array
    {
        return [
            'con' => isset($meta['total_con_contrato']) ? (int) $meta['total_con_contrato'] : null,
            'sin' => isset($meta['total_sin_contrato']) ? (int) $meta['total_sin_contrato'] : null,
        ];
    }

    /**
     * Normaliza filas de la API de Mamoré a la forma común de la tabla.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array<string, mixed>>
     */
    private function normalizarMamore(array $data): array
    {
        return app(DirectorioMamore::class)->normalizar($data);
    }

    private function paginadorVacio(Request $request, int $porPagina): LengthAwarePaginator
    {
        return new Paginador([], 0, $porPagina, 1, ['path' => $request->url(), 'query' => $request->query()]);
    }
}

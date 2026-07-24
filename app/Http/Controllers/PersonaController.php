<?php

namespace App\Http\Controllers;

use App\Exceptions\MamoreException;
use App\Models\Persona;
use App\Services\MamoreClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginador;
use Illuminate\Support\Carbon;
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
     * Listado paginado de funcionarios, con búsqueda por CI o nombre, desde la
     * fuente elegida (Mamoré por defecto, o SIAT local).
     */
    public function index(Request $request, MamoreClient $mamore): View
    {
        $this->authorize('viewAny', Persona::class);

        $busqueda = trim((string) $request->query('q', ''));
        $porPagina = $this->porPagina($request);
        $fuente = $request->query('fuente') === 'siat' ? 'siat' : 'mamore';
        $errorFuente = null;

        if ($fuente === 'siat') {
            $funcionarios = $this->funcionariosLocales($busqueda, $porPagina);
        } else {
            [$funcionarios, $errorFuente] = $this->funcionariosMamore($request, $mamore, $busqueda, $porPagina);
        }

        return view('funcionarios.index', compact('funcionarios', 'busqueda', 'porPagina', 'fuente', 'errorFuente'));
    }

    /**
     * Ficha de detalle de un funcionario local (SIAT), con sus marcaciones
     * filtradas por rango de fechas y tipo.
     */
    public function show(Request $request, Persona $persona): View
    {
        $this->authorize('view', $persona);

        $persona->loadMissing('profesion');

        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $tipo = $request->query('tipo', '');

        $marcaciones = $persona->marcaciones()
            ->when($desde, fn (Builder $query, string $d) => $query->whereDate('fecha', '>=', $d))
            ->when($hasta, fn (Builder $query, string $h) => $query->whereDate('fecha', '<=', $h))
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->paginate(25, pageName: 'marcaciones_page')
            ->withQueryString();

        return view('funcionarios.show', compact('persona', 'marcaciones', 'desde', 'hasta', 'tipo'));
    }

    /**
     * Ficha de solo lectura de una persona de la API de Mamoré (por cédula).
     */
    public function mamoreShow(string $ci, MamoreClient $mamore): View
    {
        $this->authorize('viewAny', Persona::class);

        try {
            $persona = $mamore->personByCi($ci);
        } catch (MamoreException $e) {
            abort(502, $e->getMessage());
        }

        abort_if($persona === null, 404);

        return view('funcionarios.mamore-show', ['persona' => $persona]);
    }

    /**
     * Reporte imprimible de las marcaciones «sin procesar» del funcionario:
     * todas las marcaciones crudas del rango (sin paginar, en orden
     * cronológico), con el formato del sistema de escritorio viejo.
     */
    public function reporteMarcaciones(Request $request, Persona $persona): View
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

        return view('reportes.marcaciones.sinProcesar.print', compact('persona', 'marcaciones', 'desde', 'hasta', 'tipo'));
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
            ]);
    }

    /**
     * Funcionarios de la API de Mamoré, normalizados a la forma común de la
     * tabla. Si la API falla, devuelve un paginador vacío y el mensaje de error.
     *
     * La API busca por un solo término (no cruza nombre + apellido). Para
     * buscar por varias palabras, se trae un lote por el término más largo y se
     * filtra localmente por todas las palabras.
     *
     * @return array{0: LengthAwarePaginator, 1: ?string}
     */
    private function funcionariosMamore(Request $request, MamoreClient $mamore, string $busqueda, int $porPagina): array
    {
        $terminos = preg_split('/\s+/', trim($busqueda), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $pagina = max(1, (int) $request->query('page', 1));

        try {
            if (count($terminos) <= 1) {
                $respuesta = $mamore->people($pagina, $porPagina, $busqueda);
                $meta = $respuesta['meta'] ?? [];

                $paginador = new Paginador(
                    $this->normalizarMamore($respuesta['data'] ?? []),
                    (int) ($meta['total'] ?? count($respuesta['data'] ?? [])),
                    (int) ($meta['per_page'] ?? $porPagina),
                    (int) ($meta['current_page'] ?? $pagina),
                    ['path' => $request->url(), 'query' => $request->query()],
                );

                return [$paginador, null];
            }

            $terminoMasLargo = collect($terminos)->sortByDesc(fn (string $t): int => mb_strlen($t))->first();
            $respuesta = $mamore->people(1, 100, (string) $terminoMasLargo);

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

            return [$paginador, null];
        } catch (MamoreException $e) {
            return [$this->paginadorVacio($request, $porPagina), $e->getMessage()];
        }
    }

    /**
     * Normaliza filas de la API de Mamoré a la forma común de la tabla.
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array<int, array<string, mixed>>
     */
    private function normalizarMamore(array $data): array
    {
        return collect($data)
            ->map(function (array $persona): array {
                $ci = trim((string) ($persona['ci'] ?? ''));
                $nacimiento = filled($persona['birthday'] ?? null) ? Carbon::parse($persona['birthday']) : null;

                return [
                    'id' => $persona['id'] ?? null,
                    'ci' => $ci,
                    'nombre' => trim((string) ($persona['full_name'] ?? trim(
                        ($persona['first_name'] ?? '').' '.($persona['middle_name'] ?? '').' '
                        .($persona['paternal_surname'] ?? '').' '.($persona['maternal_surname'] ?? '')
                    ))) ?: '—',
                    'profesion' => trim((string) ($persona['profession'] ?? '')),
                    // En Mamoré el PIN del reloj es la misma cédula.
                    'pinReloj' => $ci,
                    'nacimiento' => $nacimiento?->format('d/m/Y'),
                    'edad' => $nacimiento?->age,
                    'ver' => $ci !== '' ? route('funcionarios.mamore', ['ci' => $ci]) : null,
                ];
            })
            ->all();
    }

    private function paginadorVacio(Request $request, int $porPagina): LengthAwarePaginator
    {
        return new Paginador([], 0, $porPagina, 1, ['path' => $request->url(), 'query' => $request->query()]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta (solo lectura) de los funcionarios, sobre la base local MySQL
 * (tabla `personas`, migrada del SIA). El alta, edición y borrado siguen
 * siendo del sistema de escritorio.
 */
class PersonaController extends Controller
{
    /**
     * Listado paginado de funcionarios, con búsqueda por CI o nombre.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Persona::class);

        $busqueda = trim((string) $request->query('q', ''));
        $porPagina = $this->porPagina($request);

        $funcionarios = Persona::query()
            ->when($busqueda !== '', fn (Builder $query) => $query->buscar($busqueda))
            ->orderBy('paterno')
            ->paginate($porPagina)
            ->withQueryString();

        return view('funcionarios.index', compact('funcionarios', 'busqueda', 'porPagina'));
    }

    /**
     * Ficha de detalle de un funcionario, con sus marcaciones locales filtradas
     * por rango de fechas y tipo.
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
}

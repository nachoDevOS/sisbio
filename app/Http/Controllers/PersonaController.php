<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Models\Sia\Persona;
use App\Models\Sia\Profesion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * CRUD clásico (MVC) de los funcionarios del SIA (SQL Server 2008 remoto).
 *
 * Convive con el recurso Filament (que vive en /admin): mismo modelo
 * `Persona`, pero con controlador, FormRequests y vistas Blade propias y
 * personalizables. Sin borrado: eso sigue siendo del sistema de escritorio.
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

        $funcionarios = Persona::query()
            ->when($busqueda !== '', fn (Builder $query) => $query->where(fn (Builder $sub) => $sub
                ->where('IdPersona', 'like', "%{$busqueda}%")
                ->orWhere('Nombres', 'like', "%{$busqueda}%")
                ->orWhere('Paterno', 'like', "%{$busqueda}%")
                ->orWhere('Materno', 'like', "%{$busqueda}%")))
            ->orderBy('Paterno')
            ->paginate(25)
            ->withQueryString();

        return view('funcionarios.index', compact('funcionarios', 'busqueda'));
    }

    /**
     * Formulario de alta.
     */
    public function create(): View
    {
        $this->authorize('create', Persona::class);

        return view('funcionarios.create', $this->datosDeFormulario());
    }

    /**
     * Guarda un funcionario nuevo. La validación la hace StorePersonaRequest.
     * MarcaDirecta es NOT NULL sin default en el SQL Server y el formulario
     * la tiene deshabilitada: el INSERT siempre la manda en falso.
     */
    public function store(StorePersonaRequest $request): RedirectResponse
    {
        $this->authorize('create', Persona::class);

        Persona::create($request->validated() + ['MarcaDirecta' => false]);

        return redirect()
            ->route('funcionarios.index')
            ->with('estado', 'Funcionario registrado correctamente.');
    }

    /**
     * Ficha de detalle de un funcionario, con sus marcaciones del SIA
     * filtradas por rango de fechas y tipo (mismo criterio de solo lectura
     * que MarcacionController).
     */
    public function show(Request $request, Persona $persona): View
    {
        $this->authorize('view', $persona);

        $persona->loadMissing('profesion');

        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $tipo = $request->query('tipo', '');

        $marcaciones = $persona->marcaciones()
            ->when($desde, fn (Builder $query, string $d) => $query->whereDate('Fecha', '>=', $d))
            ->when($hasta, fn (Builder $query, string $h) => $query->whereDate('Fecha', '<=', $h))
            ->when($tipo !== '', fn (Builder $query) => $query->where('Tipo', $tipo))
            ->orderByDesc('Fecha')
            ->orderByDesc('Hora')
            ->paginate(25, pageName: 'marcaciones_page')
            ->withQueryString();

        return view('funcionarios.show', compact('persona', 'marcaciones', 'desde', 'hasta', 'tipo'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Persona $persona): View
    {
        $this->authorize('update', $persona);

        return view('funcionarios.edit', ['persona' => $persona] + $this->datosDeFormulario());
    }

    /**
     * Actualiza un funcionario. La validación la hace UpdatePersonaRequest;
     * el carnet (clave primaria) y el control de asistencia no se tocan.
     */
    public function update(UpdatePersonaRequest $request, Persona $persona): RedirectResponse
    {
        $this->authorize('update', $persona);

        $persona->update($request->validated());

        return redirect()
            ->route('funcionarios.index')
            ->with('estado', 'Funcionario actualizado correctamente.');
    }

    /**
     * Catálogos que necesitan los formularios de alta y edición.
     *
     * @return array{profesiones: Collection<string, string>, niveles: list<string>}
     */
    private function datosDeFormulario(): array
    {
        return [
            'profesiones' => Profesion::query()
                ->orderBy('NombreProfesion')
                ->pluck('NombreProfesion', 'CodigoProfesion'),
            'niveles' => StorePersonaRequest::NIVELES_ESTUDIO,
        ];
    }
}

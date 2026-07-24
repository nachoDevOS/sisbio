<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiaExcepcionalRequest;
use App\Http\Requests\UpdateDiaExcepcionalRequest;
use App\Models\DiaExcepcional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * CRUD clásico (MVC) de los días excepcionales (feriados/tolerancias que no
 * controlan asistencia), sobre la base local MySQL. Eliminación lógica.
 */
class DiaExcepcionalController extends Controller
{
    /**
     * Listado paginado, de la fecha más reciente a la más antigua, con búsqueda
     * por motivo o por fecha (año, `YYYY-MM-DD` o `dd/mm/YYYY`).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DiaExcepcional::class);

        $busqueda = trim((string) $request->query('q', ''));
        $porPagina = $this->porPagina($request);

        $diasExcepcionales = DiaExcepcional::query()
            ->when($busqueda !== '', fn (Builder $query) => $this->filtrarBusqueda($query, $busqueda))
            ->orderByDesc('fecha')
            ->paginate($porPagina)
            ->withQueryString();

        return view('dias-excepcionales.index', compact('diasExcepcionales', 'busqueda', 'porPagina'));
    }

    /**
     * Aplica la búsqueda: siempre por motivo (texto parcial) y, si el término
     * parece una fecha, también por la columna `fecha`.
     */
    private function filtrarBusqueda(Builder $query, string $busqueda): Builder
    {
        return $query->where(function (Builder $query) use ($busqueda): void {
            $query->where('motivoInasistencia', 'like', "%{$busqueda}%");

            if (preg_match('/^\d{4}$/', $busqueda)) {
                $query->orWhereYear('fecha', $busqueda);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $busqueda)) {
                $query->orWhereDate('fecha', $busqueda);
            } elseif (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $busqueda)) {
                $query->orWhereDate('fecha', Carbon::createFromFormat('d/m/Y', $busqueda)->toDateString());
            }
        });
    }

    /**
     * Formulario de alta.
     */
    public function create(): View
    {
        $this->authorize('create', DiaExcepcional::class);

        return view('dias-excepcionales.create');
    }

    /**
     * Guarda un día excepcional nuevo. La validación la hace el Request.
     */
    public function store(StoreDiaExcepcionalRequest $request): RedirectResponse
    {
        $this->authorize('create', DiaExcepcional::class);

        DiaExcepcional::create($request->validated());

        return redirect()
            ->route('dias-excepcionales.index')
            ->with('estado', 'Día excepcional registrado correctamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(DiaExcepcional $diaExcepcional): View
    {
        $this->authorize('update', $diaExcepcional);

        return view('dias-excepcionales.edit', ['diaExcepcional' => $diaExcepcional]);
    }

    /**
     * Actualiza un día excepcional. La validación la hace el Request.
     */
    public function update(UpdateDiaExcepcionalRequest $request, DiaExcepcional $diaExcepcional): RedirectResponse
    {
        $this->authorize('update', $diaExcepcional);

        $diaExcepcional->update($request->validated());

        return redirect()
            ->route('dias-excepcionales.index')
            ->with('estado', 'Día excepcional actualizado correctamente.');
    }

    /**
     * Elimina (lógicamente) un día excepcional.
     */
    public function destroy(DiaExcepcional $diaExcepcional): RedirectResponse
    {
        $this->authorize('delete', $diaExcepcional);

        $diaExcepcional->delete();

        return redirect()
            ->route('dias-excepcionales.index')
            ->with('estado', 'Día excepcional eliminado.');
    }
}

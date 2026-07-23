<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiaTurnoRequest;
use App\Http\Requests\UpdateDiaTurnoRequest;
use App\Models\Sia\DiaTurno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD clásico (MVC) de los horarios (turnos) del SIA: el equivalente web del
 * «Administrador de horarios» del sistema de escritorio. Trabaja sobre la tabla
 * legada DiaTurnos (SQL Server 2008 R2 remoto).
 */
class DiaTurnoController extends Controller
{
    /**
     * Listado de horarios, ordenados por día y nombre.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', DiaTurno::class);

        $buscar = trim((string) $request->query('buscar', ''));
        $dia = (string) $request->query('dia', '');

        $horarios = DiaTurno::query()
            ->when($buscar !== '', fn (Builder $query) => $query->where('NombreTurno', 'like', "%{$buscar}%"))
            ->when($dia !== '', fn (Builder $query) => $query->where('Dia', $dia))
            ->ordenado()
            ->paginate(25)
            ->withQueryString();

        return view('horarios.index', compact('horarios', 'buscar', 'dia'));
    }

    /**
     * Ficha de solo lectura de un horario.
     */
    public function show(DiaTurno $horario): View
    {
        $this->authorize('view', $horario);

        return view('horarios.show', compact('horario'));
    }

    /**
     * Formulario de alta.
     */
    public function create(): View
    {
        $this->authorize('create', DiaTurno::class);

        return view('horarios.create');
    }

    /**
     * Guarda un horario nuevo. El código del turno (char(3), clave) se genera
     * automático como en el sistema de escritorio: el formulario no lo pide.
     */
    public function store(StoreDiaTurnoRequest $request): RedirectResponse
    {
        $this->authorize('create', DiaTurno::class);

        $horario = new DiaTurno;
        $horario->IdTurno = $this->generarCodigo();
        $this->asignarDatos($horario, $request->validated());
        $horario->save();

        return redirect()
            ->route('horarios.index')
            ->with('estado', 'Horario registrado correctamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(DiaTurno $horario): View
    {
        $this->authorize('update', $horario);

        return view('horarios.edit', compact('horario'));
    }

    /**
     * Actualiza un horario. El IdTurno (clave) no se toca.
     */
    public function update(UpdateDiaTurnoRequest $request, DiaTurno $horario): RedirectResponse
    {
        $this->authorize('update', $horario);

        $this->asignarDatos($horario, $request->validated());
        $horario->save();

        return redirect()
            ->route('horarios.index')
            ->with('estado', 'Horario actualizado correctamente.');
    }

    /**
     * Elimina un horario.
     */
    public function destroy(DiaTurno $horario): RedirectResponse
    {
        $this->authorize('delete', $horario);

        try {
            $horario->delete();
        } catch (QueryException $e) {
            // La tabla DiaTurnos está referenciada por Licencias, asignaciones,
            // etc. Si el turno está en uso, SQL Server rechaza el DELETE por la
            // clave foránea: se avisa en vez de reventar con un error 500.
            return redirect()
                ->route('horarios.index')
                ->with('error', 'No se puede eliminar el horario: está en uso por licencias, asignaciones u otros registros del SIA.');
        }

        return redirect()
            ->route('horarios.index')
            ->with('estado', 'Horario eliminado.');
    }

    /**
     * Copia los datos validados al modelo, convirtiendo cada "HH:MM" del
     * formulario a datetime sobre la fecha base 1899-12-30 (patrón del SIA).
     *
     * @param  array<string, mixed>  $datos
     */
    private function asignarDatos(DiaTurno $horario, array $datos): void
    {
        $horario->Dia = $datos['Dia'];
        $horario->NombreTurno = $datos['NombreTurno'];

        foreach (['HEntrada', 'HTolerancia', 'EMinima', 'EMaxima', 'HSalida', 'STolerancia', 'SMinima', 'SMaxima'] as $campo) {
            $horario->{$campo} = Carbon::createFromFormat('Y-m-d H:i', '1899-12-30 '.$datos[$campo]);
        }

        $horario->HTrabajadas = $datos['HTrabajadas'];
        $horario->SiguienteDia = $datos['SiguienteDia'] ?? false;
    }

    /**
     * Genera un código de turno único de 3 caracteres [A-Z0-9], como los que
     * ya usa la tabla (ej. 011, 0A4, 0ZX).
     */
    private function generarCodigo(): string
    {
        do {
            $codigo = Str::upper(Str::random(3));
        } while (DiaTurno::query()->where('IdTurno', $codigo)->exists());

        return $codigo;
    }
}

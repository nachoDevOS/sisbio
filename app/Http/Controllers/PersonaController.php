<?php

namespace App\Http\Controllers;

use App\Models\Sia\Persona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Listado de solo lectura de los funcionarios del SIA (SQL Server 2008 remoto).
 *
 * Solo `index`: el panel nunca escribe sobre esa base legada, igual que el
 * recurso Filament equivalente que tampoco expone alta/edición.
 */
class PersonaController extends Controller
{
    /**
     * Listado paginado de funcionarios, con búsqueda por CI o nombre.
     */
    public function index(Request $request): View
    {
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
}

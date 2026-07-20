<?php

namespace App\Http\Controllers;

use App\Exceptions\DeviceServiceException;
use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;
use App\Models\Equipo;
use App\Services\DeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD clásico (MVC) de los equipos biométricos.
 *
 * Convive con el recurso Filament (que vive en /admin): este controlador
 * expone el mismo modelo `Equipo` como páginas Blade tradicionales en /equipos,
 * con controlador, FormRequests y vistas propias.
 */
class EquipoController extends Controller
{
    /**
     * Listado de equipos, del más reciente al más antiguo.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Equipo::class);

        $equipos = Equipo::latest()->paginate(15);

        return view('equipos.index', compact('equipos'));
    }

    /**
     * Formulario de alta.
     */
    public function create(): View
    {
        $this->authorize('create', Equipo::class);

        return view('equipos.create');
    }

    /**
     * Guarda un equipo nuevo. La validación la hace StoreEquipoRequest.
     */
    public function store(StoreEquipoRequest $request): RedirectResponse
    {
        $this->authorize('create', Equipo::class);

        Equipo::create($request->validated());

        return redirect()
            ->route('equipos.index')
            ->with('estado', 'Equipo registrado correctamente.');
    }

    /**
     * Ficha de un equipo.
     */
    public function show(Equipo $equipo): View
    {
        $this->authorize('view', $equipo);

        return view('equipos.show', compact('equipo'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Equipo $equipo): View
    {
        $this->authorize('update', $equipo);

        return view('equipos.edit', compact('equipo'));
    }

    /**
     * Actualiza un equipo. La validación la hace UpdateEquipoRequest.
     */
    public function update(UpdateEquipoRequest $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('update', $equipo);

        $equipo->update($request->validated());

        return redirect()
            ->route('equipos.index')
            ->with('estado', 'Equipo actualizado correctamente.');
    }

    /**
     * Elimina un equipo.
     */
    public function destroy(Equipo $equipo): RedirectResponse
    {
        $this->authorize('delete', $equipo);

        $equipo->delete();

        return redirect()
            ->route('equipos.index')
            ->with('estado', 'Equipo eliminado.');
    }

    /**
     * Se conecta al equipo real vía el microservicio y actualiza su estado
     * (en línea, algoritmo detectado, última conexión). Mismo criterio que
     * la acción "Probar conexión" del recurso Filament.
     */
    public function probarConexion(Equipo $equipo, DeviceService $deviceService): RedirectResponse
    {
        $this->authorize('update', $equipo);

        try {
            $info = $deviceService->info($equipo);

            $equipo->update([
                'en_linea' => true,
                'algoritmo' => $info['algoritmo'] ?? $equipo->algoritmo,
                'ultima_sync' => now(),
            ]);

            return back()->with('estado', "Conectado a «{$equipo->nombre}». Algoritmo: ".($info['algoritmo'] ?? 'N/D'));
        } catch (DeviceServiceException $e) {
            $equipo->update(['en_linea' => false]);

            return back()->with('error', "No se pudo conectar: {$e->getMessage()}");
        }
    }

    /**
     * Lee en vivo las marcaciones guardadas en el equipo (vía el
     * microservicio) y las muestra. Mismo criterio que la acción "Ver
     * marcaciones" del recurso Filament: nunca se escribe nada aquí.
     */
    public function marcaciones(Equipo $equipo, DeviceService $deviceService): View
    {
        $this->authorize('view', $equipo);

        try {
            $respuesta = $deviceService->attendance($equipo);
            $marcaciones = $respuesta['marcaciones'] ?? [];
            $error = null;
        } catch (DeviceServiceException $e) {
            $marcaciones = [];
            $error = $e->getMessage();
        }

        return view('equipos.marcaciones', compact('equipo', 'marcaciones', 'error'));
    }
}

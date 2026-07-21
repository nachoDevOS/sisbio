<?php

namespace App\Http\Controllers;

use App\Exceptions\DeviceServiceException;
use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;
use App\Models\Equipo;
use App\Services\DeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD clásico (MVC) de los equipos biométricos.
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
     * microservicio) y las muestra. Nunca se escribe nada aquí.
     *
     * El equipo devuelve todo el historial en una sola respuesta (el
     * protocolo ZK no pagina), así que la paginación se arma acá, sobre el
     * array ya recibido.
     */
    public function marcaciones(Request $request, Equipo $equipo, DeviceService $deviceService): View
    {
        $this->authorize('view', $equipo);

        [$todas, $error] = $this->marcacionesDelEquipo($equipo, $deviceService);

        $porPagina = 15;
        $pagina = LengthAwarePaginator::resolveCurrentPage();

        $marcaciones = new LengthAwarePaginator(
            array_slice($todas, ($pagina - 1) * $porPagina, $porPagina),
            count($todas),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('equipos.marcaciones', compact('equipo', 'marcaciones', 'error'));
    }

    /**
     * Descarga el mismo historial de marcaciones en CSV (todo, sin paginar).
     */
    public function exportarMarcaciones(Equipo $equipo, DeviceService $deviceService): Response|RedirectResponse
    {
        $this->authorize('view', $equipo);

        [$todas, $error] = $this->marcacionesDelEquipo($equipo, $deviceService);

        if ($error) {
            return back()->with('error', $error);
        }

        $csv = "\u{FEFF}CI/ID,Nombre,Fecha,Hora\n";

        foreach ($todas as $marcacion) {
            $fecha = $marcacion['timestamp'] ? Carbon::parse($marcacion['timestamp']) : null;
            $nombre = filled($marcacion['nombre'] ?? null) ? $marcacion['nombre'] : 'Sin nombre';

            $csv .= implode(',', [
                $marcacion['user_id'],
                '"'.str_replace('"', '""', $nombre).'"',
                $fecha?->format('d/m/Y') ?? '',
                $fecha?->format('H:i:s') ?? '',
            ])."\n";
        }

        $archivo = 'marcaciones-'.Str::slug($equipo->nombre).'-'.now()->format('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$archivo}\"",
        ]);
    }

    /**
     * Trae (y cachea 2 minutos) el historial completo de marcaciones del
     * equipo. Sin la caché, cada página o exportación repetiría la lectura
     * completa del reloj (miles de registros) para el mismo dato.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string}
     */
    private function marcacionesDelEquipo(Equipo $equipo, DeviceService $deviceService): array
    {
        try {
            $todas = Cache::remember(
                "equipos.{$equipo->id}.marcaciones",
                now()->addMinutes(2),
                fn (): array => $deviceService->attendance($equipo)['marcaciones'] ?? [],
            );

            return [$todas, null];
        } catch (DeviceServiceException $e) {
            return [[], $e->getMessage()];
        }
    }
}

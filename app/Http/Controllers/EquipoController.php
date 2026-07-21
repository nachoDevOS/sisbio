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
     * Descarga el historial de marcaciones del equipo en CSV, opcionalmente
     * acotado a un rango de fechas (parámetros `desde`/`hasta`). Se lee en vivo
     * del equipo vía el microservicio; nunca se muestra en pantalla (el
     * historial es grande y renderizarlo es lento), solo se baja el archivo.
     */
    public function exportarMarcaciones(Request $request, Equipo $equipo, DeviceService $deviceService): Response|RedirectResponse
    {
        $this->authorize('view', $equipo);

        [$todas, $error] = $this->marcacionesDelEquipo($equipo, $deviceService);

        if ($error) {
            return back()->with('error', $error);
        }

        $todas = $this->filtrarPorRango($todas, $request->query('desde', ''), $request->query('hasta', ''));

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
     * Trae (y cachea 15 minutos) el historial completo de marcaciones del
     * equipo. La lectura del reloj por el protocolo ZK trae todo el buffer de
     * una (miles de registros, es lenta por naturaleza); con la caché solo la
     * primera carga la paga, y después filtrar por rango, paginar o descargar
     * el CSV usan el mismo dato ya en memoria sin volver a pegarle al equipo.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string}
     */
    private function marcacionesDelEquipo(Equipo $equipo, DeviceService $deviceService): array
    {
        try {
            $todas = Cache::remember(
                "equipos.{$equipo->id}.marcaciones",
                now()->addMinutes(15),
                fn (): array => $deviceService->attendance($equipo)['marcaciones'] ?? [],
            );

            return [$todas, null];
        } catch (DeviceServiceException $e) {
            return [[], $e->getMessage()];
        }
    }

    /**
     * Filtra el array de marcaciones ya traídas del equipo por rango de
     * fechas (inclusive). `$desde`/`$hasta` vacíos no filtran ese extremo.
     *
     * @param  array<int, array<string, mixed>>  $todas
     * @return array<int, array<string, mixed>>
     */
    private function filtrarPorRango(array $todas, string $desde, string $hasta): array
    {
        if ($desde === '' && $hasta === '') {
            return $todas;
        }

        $inicio = $desde !== '' ? Carbon::parse($desde)->startOfDay() : null;
        $fin = $hasta !== '' ? Carbon::parse($hasta)->endOfDay() : null;

        return array_values(array_filter($todas, function (array $marcacion) use ($inicio, $fin): bool {
            if (blank($marcacion['timestamp'] ?? null)) {
                return false;
            }

            $fecha = Carbon::parse($marcacion['timestamp']);

            return (! $inicio || $fecha->greaterThanOrEqualTo($inicio))
                && (! $fin || $fecha->lessThanOrEqualTo($fin));
        }));
    }
}

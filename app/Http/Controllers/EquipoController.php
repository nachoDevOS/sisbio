<?php

namespace App\Http\Controllers;

use App\Exceptions\DeviceServiceException;
use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;
use App\Models\Asistencia;
use App\Models\Equipo;
use App\Services\DeviceService;
use App\Services\RegistroAsistencia;
use Illuminate\Database\Eloquent\Builder;
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
     * Listado de equipos, del más reciente al más antiguo, con búsqueda por
     * nombre, IP o ubicación.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipo::class);

        $busqueda = trim((string) $request->query('q', ''));
        $porPagina = $this->porPagina($request);

        $equipos = Equipo::query()
            ->when($busqueda !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('ip', 'like', "%{$busqueda}%")
                ->orWhere('ubicacion', 'like', "%{$busqueda}%")))
            ->latest()
            ->paginate($porPagina)
            ->withQueryString();

        return view('equipos.index', compact('equipos', 'busqueda', 'porPagina'));
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
     * (en línea, algoritmo detectado, última conexión).
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

        $desde = (string) $request->query('desde', '');
        $hasta = (string) $request->query('hasta', '');

        [$todas, $error] = $this->marcacionesDelEquipo($equipo, $deviceService, $desde, $hasta, fresco: true);

        if ($error) {
            return back()->with('error', $error);
        }

        $todas = $this->filtrarPorRango($todas, $desde, $hasta);

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
     * Lee las marcaciones del equipo (opcionalmente acotadas por rango) y las
     * registra directo en la tabla local `asistencias` (MySQL), sin pasar por
     * descargar/reimportar el CSV. Aplica las mismas reglas que el import (cruce
     * por funcionario, sin duplicar, descartando fecha basura del reloj).
     */
    public function sincronizarMarcaciones(Request $request, Equipo $equipo, DeviceService $deviceService, RegistroAsistencia $registro): RedirectResponse
    {
        $this->authorize('create', Asistencia::class);

        $desde = (string) $request->input('desde', '');
        $hasta = (string) $request->input('hasta', '');

        [$todas, $error] = $this->marcacionesDelEquipo($equipo, $deviceService, $desde, $hasta, fresco: true);

        if ($error) {
            return back()->with('error', $error);
        }

        $todas = $this->filtrarPorRango($todas, $desde, $hasta);

        $filas = array_map(fn (array $marcacion): array => [
            'ci' => $marcacion['user_id'] ?? null,
            'momento' => filled($marcacion['timestamp'] ?? null) ? Carbon::parse($marcacion['timestamp']) : null,
        ], $todas);

        $conteo = $registro->registrar($filas);

        return back()->with('estado', $registro->mensaje($conteo, "Sincronización de «{$equipo->nombre}»"));
    }

    /**
     * Trae las marcaciones del equipo para el rango pedido, leyéndolas en vivo
     * del reloj vía el microservicio. El rango se pasa al microservicio, que lo
     * aplica antes de responder: así, en equipos con historial largo, Laravel
     * recibe y parsea mucho menos.
     *
     * Con `$fresco` se ignora la caché y se relee siempre (para exportar/enviar,
     * que deben traer lo del momento). La caché de 15 min por rango solo evita
     * repetir la MISMA lectura dentro de la ventana cuando no se fuerza fresco.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: ?string}
     */
    private function marcacionesDelEquipo(Equipo $equipo, DeviceService $deviceService, string $desde = '', string $hasta = '', bool $fresco = false): array
    {
        $clave = "equipos.{$equipo->id}.marcaciones.{$desde}.{$hasta}";

        if ($fresco) {
            Cache::forget($clave);
        }

        try {
            $todas = Cache::remember(
                $clave,
                now()->addMinutes(15),
                fn (): array => $deviceService->attendance($equipo, $desde ?: null, $hasta ?: null)['marcaciones'] ?? [],
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

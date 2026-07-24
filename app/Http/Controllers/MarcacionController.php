<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportarMarcacionesRequest;
use App\Http\Requests\StoreMarcacionRequest;
use App\Models\Asistencia;
use App\Services\RegistroAsistencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Listado de las marcaciones desde la base local (MySQL, tabla `asistencias`,
 * migrada del SIA) y la importación del CSV que exporta EquipoController.
 *
 * La tabla tiene ~4.4 millones de filas, por eso el rango arranca en el mes
 * actual: nunca se lista ni se cuenta la tabla completa.
 *
 * Tanto el listado como el import (y la sincronización de equipos) trabajan ya
 * sobre la base local MySQL vía App\Services\RegistroAsistencia.
 */
class MarcacionController extends Controller
{
    /**
     * Listado paginado de marcaciones, filtrado por rango de fechas.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Asistencia::class);

        // Por defecto: del 1.º del mes hasta hoy (deja fuera las fechas basura
        // futuras que arrastra el SIA, ej. años 2064/2103).
        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());
        $buscar = trim((string) $request->query('buscar', ''));
        $tipo = $request->query('tipo', '');
        $porPagina = $this->porPagina($request);

        $marcaciones = Asistencia::query()
            ->with('persona')
            ->when($desde, fn (Builder $query, string $d) => $query->whereDate('fecha', '>=', $d))
            ->when($hasta, fn (Builder $query, string $h) => $query->whereDate('fecha', '<=', $h))
            ->when($buscar !== '', fn (Builder $query) => $query->buscar($buscar))
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->paginate($porPagina)
            ->withQueryString();

        return view('marcaciones.index', compact('marcaciones', 'desde', 'hasta', 'buscar', 'tipo', 'porPagina'));
    }

    /**
     * Registra una marcación manual (tipo M) sobre la base local. La hora se
     * guarda sobre la fecha base 1899-12-30, como el resto de las marcaciones.
     */
    public function store(StoreMarcacionRequest $request): RedirectResponse
    {
        $this->authorize('create', Asistencia::class);

        $ci = $request->validated('ci');
        $fecha = Carbon::parse($request->validated('fecha'))->startOfDay();
        $hora = Carbon::parse($request->validated('hora'))->format('H:i:s');

        $yaExiste = Asistencia::query()
            ->where('ci', $ci)
            ->whereDate('fecha', $fecha->toDateString())
            ->whereTime('hora', $hora)
            ->exists();

        if ($yaExiste) {
            return back()->with('error', 'Ya existe una marcación para ese funcionario en esa fecha y hora.');
        }

        Asistencia::create([
            'ci' => $ci,
            'fecha' => $fecha,
            'hora' => '1899-12-30 '.$hora,
            'tipo' => Asistencia::TIPO_MANUAL,
        ]);

        return redirect()
            ->route('marcaciones.index')
            ->with('estado', 'Marcación manual registrada correctamente.');
    }

    /**
     * Importa a la tabla local `asistencias` el CSV que ya genera
     * EquipoController::exportarMarcaciones() (columnas CI/ID, Nombre,
     * Fecha, Hora). El CI se cruza contra `personas.ci`; lo que no matchea un
     * funcionario o ya existe (mismo ci+fecha+hora) se cuenta pero no se inserta.
     */
    public function importar(ImportarMarcacionesRequest $request, RegistroAsistencia $registro): RedirectResponse
    {
        $this->authorize('create', Asistencia::class);

        $ruta = $request->file('archivo')->getRealPath();
        $separador = $this->detectarSeparador($ruta);
        $manejador = fopen($ruta, 'r');

        $filas = [];
        $esPrimeraFila = true;

        while (($columnas = fgetcsv($manejador, 0, $separador)) !== false) {
            // Salta las líneas en blanco que suele dejar Excel al final.
            if (count(array_filter($columnas, fn ($celda): bool => trim((string) $celda) !== '')) === 0) {
                continue;
            }

            [$ci, , $fechaCsv, $horaCsv] = array_pad($columnas, 4, null);

            $fecha = $this->parsearFecha(trim((string) $fechaCsv));
            $hora = $this->parsearHora(trim((string) $horaCsv));

            // La primera fila que no parsea como fecha/hora es el encabezado: se
            // descarta sin contarla. El resto de filas ilegibles van como
            // inválidas (momento nulo) para que el servicio las cuente.
            if ((! $fecha || ! $hora) && $esPrimeraFila) {
                $esPrimeraFila = false;

                continue;
            }

            $esPrimeraFila = false;

            $filas[] = [
                'ci' => $ci,
                'momento' => $fecha && $hora ? $fecha->copy()->setTime($hora->hour, $hora->minute, $hora->second) : null,
            ];
        }

        fclose($manejador);

        $conteo = $registro->registrar($filas);

        return back()->with('estado', $registro->mensaje($conteo));
    }

    /**
     * Detecta el separador del CSV. Excel en español guarda con ';', mientras
     * que el que exporta el sistema usa ','. Se decide por el que más aparece
     * en la primera línea.
     */
    private function detectarSeparador(string $ruta): string
    {
        $manejador = fopen($ruta, 'r');
        $primeraLinea = (string) fgets($manejador);
        fclose($manejador);

        return substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
    }

    /**
     * Parsea la fecha probando los formatos que puede dejar el export propio
     * (d/m/Y) o un reguardado desde Excel (d-m-Y, ISO). Devuelve la fecha a
     * medianoche, o null si ninguno encaja.
     */
    private function parsearFecha(string $valor): ?Carbon
    {
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $formato) {
            try {
                return Carbon::createFromFormat('!'.$formato, $valor);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Parsea la hora con o sin segundos. Devuelve null si no encaja.
     */
    private function parsearHora(string $valor): ?Carbon
    {
        foreach (['H:i:s', 'H:i'] as $formato) {
            try {
                return Carbon::createFromFormat('!'.$formato, $valor);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}

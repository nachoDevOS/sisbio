<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportarMarcacionesRequest;
use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Listado de las marcaciones del SIA (SQL Server 2008 remoto) y su única
 * puerta de escritura: importar el CSV que ya exporta EquipoController.
 *
 * La tabla tiene ~4.4 millones de filas, por eso el rango arranca en el mes
 * actual: nunca se lista ni se cuenta la tabla completa.
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

        $marcaciones = Asistencia::query()
            ->with('persona')
            ->when($desde, fn (Builder $query, string $d) => $query->whereDate('Fecha', '>=', $d))
            ->when($hasta, fn (Builder $query, string $h) => $query->whereDate('Fecha', '<=', $h))
            ->when($buscar !== '', fn (Builder $query) => $query
                ->where(fn (Builder $condiciones) => $condiciones
                    ->where('IdPersona', 'like', "%{$buscar}%")
                    ->orWhereHas('persona', fn (Builder $subCondiciones) => $subCondiciones
                        ->where(fn (Builder $nombreCondiciones) => $nombreCondiciones
                            ->where('Nombres', 'like', "%{$buscar}%")
                            ->orWhere('Paterno', 'like', "%{$buscar}%")
                            ->orWhere('Materno', 'like', "%{$buscar}%")))))
            ->when($tipo !== '', fn (Builder $query) => $query->where('Tipo', $tipo))
            ->orderByDesc('Fecha')
            ->orderByDesc('Hora')
            ->paginate(25)
            ->withQueryString();

        return view('marcaciones.index', compact('marcaciones', 'desde', 'hasta', 'buscar', 'tipo'));
    }

    /**
     * Importa a Asistencia el CSV que ya genera
     * EquipoController::exportarMarcaciones() (columnas CI/ID, Nombre,
     * Fecha, Hora). El CI se cruza contra Personas.IdPersona con el mismo
     * criterio de padding que Persona::resolveRouteBinding(); lo que no
     * matchea un funcionario o ya existe en Asistencia (misma
     * IdPersona+Fecha+Hora) se cuenta pero no se inserta.
     */
    public function importar(ImportarMarcacionesRequest $request): RedirectResponse
    {
        $this->authorize('create', Asistencia::class);

        $ruta = $request->file('archivo')->getRealPath();
        $separador = $this->detectarSeparador($ruta);
        $manejador = fopen($ruta, 'r');

        $insertadas = 0;
        $existentes = 0;
        $sinFuncionario = 0;
        $invalidas = 0;
        $esPrimeraFila = true;

        while (($fila = fgetcsv($manejador, 0, $separador)) !== false) {
            // Salta las líneas en blanco que suele dejar Excel al final.
            if (count(array_filter($fila, fn ($celda): bool => trim((string) $celda) !== '')) === 0) {
                continue;
            }

            [$ci, , $fechaCsv, $horaCsv] = array_pad($fila, 4, null);

            $fecha = $this->parsearFecha(trim((string) $fechaCsv));
            $hora = $this->parsearHora(trim((string) $horaCsv));

            if (! $fecha || ! $hora) {
                // La primera fila que no parsea como fecha/hora es el encabezado.
                if ($esPrimeraFila) {
                    $esPrimeraFila = false;

                    continue;
                }

                $invalidas++;

                continue;
            }

            $esPrimeraFila = false;

            // El reloj a veces arrastra fecha basura por la batería del RTC
            // (años tipo 2064/2103, mismo problema que filtra el listado por
            // defecto). Esas filas desbordan la columna Fecha del SIA y
            // tiran abajo el insert si se dejan pasar.
            if ($fecha->isFuture()) {
                $invalidas++;

                continue;
            }

            $persona = (new Persona)->resolveRouteBinding(trim((string) $ci));

            if (! $persona) {
                $sinFuncionario++;

                continue;
            }

            $yaExiste = Asistencia::query()
                ->where('IdPersona', $persona->IdPersona)
                ->whereDate('Fecha', $fecha->toDateString())
                ->whereTime('Hora', $hora->format('H:i:s'))
                ->exists();

            if ($yaExiste) {
                $existentes++;

                continue;
            }

            try {
                Asistencia::create([
                    'IdPersona' => $persona->IdPersona,
                    'Fecha' => $fecha,
                    'Hora' => '1899-12-30 '.$hora->format('H:i:s'),
                    'Tipo' => Asistencia::TIPO_RELOJ,
                ]);

                $insertadas++;
            } catch (QueryException) {
                // Dato legado que el SIA rechaza (ej. otro campo fuera de rango
                // que esta fila no anticipó): se cuenta y se sigue con el resto.
                $invalidas++;
            }
        }

        fclose($manejador);

        return back()->with('estado', "Importación completa: {$insertadas} marcación(es) nueva(s), {$existentes} ya existían, {$sinFuncionario} sin funcionario vinculado, {$invalidas} fila(s) inválida(s).");
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

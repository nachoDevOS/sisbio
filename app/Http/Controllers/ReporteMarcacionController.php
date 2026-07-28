<?php

namespace App\Http\Controllers;

use App\Exceptions\MamoreException;
use App\Models\Asistencia;
use App\Models\Persona;
use App\Services\DirectorioMamore;
use App\Services\ResolutorNombres;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Reportes de marcaciones desde la base local (MySQL). Sigue el patrón de
 * selección + generación: primero se elige el funcionario y el rango, y después
 * se genera el reporte en pantalla, imprimible o en CSV según el botón usado.
 */
class ReporteMarcacionController extends Controller
{
    /**
     * Formulario de selección del reporte «marcaciones sin procesar»: busca un
     * funcionario por CI o nombre y muestra los candidatos para elegir uno.
     */
    public function sinProcesar(Request $request): View
    {
        $this->authorize('viewAny', Asistencia::class);

        $desde = $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->query('hasta', now()->toDateString());

        return view('reportes.marcaciones.sinProcesar.report', compact('desde', 'hasta'));
    }

    /**
     * Búsqueda de funcionarios por CI o nombre para el combo (select2) del
     * formulario. Devuelve hasta 20 coincidencias como JSON.
     *
     * Busca en Mamoré, que además del nombre trae el cargo y la dirección. Si la
     * API no está configurada o falla, cae a la base local (SIAT) para no dejar
     * el reporte inutilizable.
     */
    public function buscarFuncionarios(Request $request, DirectorioMamore $directorio): JsonResponse
    {
        $this->authorize('viewAny', Asistencia::class);

        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        if ($directorio->configurado()) {
            try {
                return response()->json($directorio->buscar($q)->map(fn (array $persona): array => [
                    'id' => $persona['ci'],
                    'texto' => $directorio->etiqueta($persona),
                ])->values());
            } catch (MamoreException) {
                // Sigue con la base local.
            }
        }

        $funcionarios = Persona::query()->buscar($q)->orderBy('paterno')->limit(20)->get();

        return response()->json($funcionarios->map(function (Persona $persona): array {
            $ci = trim((string) $persona->ci);
            $pin = trim((string) $persona->pinReloj);
            $nombre = $persona->nombre_completo ?: 'Sin nombre';

            return [
                'id' => $ci,
                'texto' => $ci.' — '.$nombre.($pin !== '' ? " (PIN {$pin})" : ''),
            ];
        })->values());
    }

    /**
     * Genera el reporte del funcionario elegido. El destino depende del
     * parámetro `print`: 1 = versión imprimible, 2 = CSV, otro = lista en pantalla.
     */
    public function sinProcesarList(Request $request, ResolutorNombres $resolutor): View|Response|RedirectResponse
    {
        $this->authorize('viewAny', Asistencia::class);

        $persona = $resolutor->fichaPorCi((string) $request->query('persona', ''));

        if ($persona === null) {
            return redirect()
                ->route('reportes.marcaciones.sin-procesar')
                ->with('error', 'Elegí un funcionario para generar el reporte.');
        }

        $desde = (string) $request->query('desde', now()->startOfMonth()->toDateString());
        $hasta = (string) $request->query('hasta', now()->toDateString());
        $tipo = (string) $request->query('tipo', '');

        // Las marcaciones se cruzan por CI: la ficha puede venir de Mamoré, que
        // no tiene relación con la tabla local de asistencia.
        $marcaciones = Asistencia::query()
            ->where('ci', $persona['ci'])
            ->when($desde !== '', fn (Builder $query) => $query->whereDate('fecha', '>=', $desde))
            ->when($hasta !== '', fn (Builder $query) => $query->whereDate('fecha', '<=', $hasta))
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();

        $datos = compact('persona', 'marcaciones', 'desde', 'hasta', 'tipo');

        return match ((int) $request->query('print', 0)) {
            1 => view('reportes.marcaciones.sinProcesar.print', $datos),
            2 => $this->descargarCsv($persona, $marcaciones),
            default => view('reportes.marcaciones.sinProcesar.lista', $datos),
        };
    }

    /**
     * Arma el CSV de las marcaciones (Fecha, Hora, Tipo) para descargar.
     *
     * @param  array<string, mixed>  $persona
     * @param  Collection<int, Asistencia>  $marcaciones
     */
    private function descargarCsv(array $persona, $marcaciones): Response
    {
        $csv = "\u{FEFF}Fecha,Hora,Tipo\n";

        foreach ($marcaciones as $marcacion) {
            $csv .= implode(',', [
                $marcacion->fecha?->format('d/m/Y') ?? '',
                $marcacion->hora?->format('H:i:s') ?? '',
                trim((string) $marcacion->tipo),
            ])."\n";
        }

        $archivo = 'marcaciones-'.Str::slug($persona['ci']).'-'.now()->format('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$archivo}\"",
        ]);
    }
}

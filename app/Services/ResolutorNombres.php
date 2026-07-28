<?php

namespace App\Services;

use App\Exceptions\MamoreException;
use App\Models\Persona;
use Illuminate\Support\Facades\Cache;

/**
 * Resuelve la ficha de un funcionario a partir de su CI: primero en la API
 * externa de Mamoré y, si esa persona no está ahí (o la API no está
 * configurada / falla), en la base local MySQL (`personas`).
 *
 * Lo usan los listados que muestran una columna «Funcionario» a partir de un CI
 * suelto (marcaciones, licencias) y los reportes. Solo consulta los CI distintos
 * de la página y cachea la respuesta de Mamoré por un día, para no golpear la
 * API fila por fila.
 *
 * La ficha tiene la misma forma que las filas de {@see DirectorioMamore}:
 * `ci`, `nombre`, `cargo`, `direccion`, `pinReloj`, `conContrato`, `origen`.
 *
 * @phpstan-type Ficha array{ci: string, nombre: string, nombreFormal: string, cargo: ?string, direccion: ?string, pinReloj: string, conContrato: ?bool, origen: string}
 */
class ResolutorNombres
{
    public function __construct(private MamoreClient $mamore, private DirectorioMamore $directorio) {}

    /**
     * Nombre de cada CI, con Mamoré primero y la base local como respaldo.
     *
     * @param  iterable<mixed>  $cis
     * @return array<string, ?string> ci => nombre (o null si no está en ninguno)
     */
    public function porCi(iterable $cis): array
    {
        return collect($this->fichasPorCi($cis))
            ->map(fn (?array $ficha): ?string => $ficha['nombre'] ?? null)
            ->all();
    }

    /**
     * Ficha de cada CI (nombre, cargo y dirección), con Mamoré primero y la base
     * local como respaldo.
     *
     * @param  iterable<mixed>  $cis
     * @return array<string, ?array<string, mixed>> ci => ficha (o null si no está en ninguno)
     */
    public function fichasPorCi(iterable $cis): array
    {
        $cis = collect($cis)
            ->map(fn ($ci): string => trim((string) $ci))
            ->filter()
            ->unique()
            ->values();

        if ($cis->isEmpty()) {
            return [];
        }

        $locales = Persona::query()
            ->with('profesion')
            ->whereIn('ci', $cis->all())
            ->get()
            ->keyBy(fn (Persona $persona): string => trim((string) $persona->ci));

        $usarMamore = $this->mamore->configurado();
        $fichas = [];

        foreach ($cis as $ci) {
            $ficha = $usarMamore ? $this->fichaMamore($ci) : null;

            if ($ficha === null && $locales->has($ci)) {
                $ficha = $this->fichaLocal($locales->get($ci));
            }

            $fichas[$ci] = $ficha;
        }

        return $fichas;
    }

    /**
     * Ficha de un solo funcionario. `null` si no está ni en Mamoré ni local.
     *
     * @return array<string, mixed>|null
     */
    public function fichaPorCi(string $ci): ?array
    {
        return $this->fichasPorCi([$ci])[trim($ci)] ?? null;
    }

    /**
     * Ficha de una persona en Mamoré por CI, cacheada por un día. Devuelve
     * `null` si no existe (404). Un fallo transitorio de la API no se cachea (se
     * reintenta luego) y también devuelve `null`.
     *
     * @return array<string, mixed>|null
     */
    private function fichaMamore(string $ci): ?array
    {
        $clave = 'mamore.ficha.'.$ci;
        $cacheado = Cache::get($clave);

        // El '' cacheado es «Mamoré no tiene a esta persona»: se respeta para no
        // repetir la consulta durante el día.
        if ($cacheado !== null) {
            return $cacheado === '' ? null : $cacheado;
        }

        try {
            $persona = $this->mamore->personByCi($ci);
        } catch (MamoreException) {
            return null;
        }

        $ficha = $persona === null ? null : $this->desdeMamore($persona);

        Cache::put($clave, $ficha ?? '', now()->addDay());

        return $ficha;
    }

    /**
     * Normaliza una persona de Mamoré (con su contrato embebido) a la ficha.
     *
     * @param  array<string, mixed>  $persona
     * @return array<string, mixed>
     */
    private function desdeMamore(array $persona): array
    {
        $fila = $this->directorio->normalizarPersona($persona);

        return [
            'ci' => $fila['ci'],
            'nombre' => $fila['nombre'],
            'nombreFormal' => $fila['nombreFormal'] ?: $fila['nombre'],
            'cargo' => $fila['cargo'] ?: null,
            'direccion' => $fila['direccion'] ?: null,
            'pinReloj' => $fila['pinReloj'],
            'conContrato' => $fila['conContrato'],
            'origen' => 'mamore',
        ];
    }

    /**
     * Ficha de respaldo desde la base local: SIAT no conoce los contratos, así
     * que no hay cargo ni dirección administrativa.
     *
     * @return array<string, mixed>
     */
    private function fichaLocal(Persona $persona): array
    {
        return [
            'ci' => trim((string) $persona->ci),
            'nombre' => $persona->nombre_completo ?: '—',
            'nombreFormal' => collect([$persona->paterno, $persona->materno, $persona->nombres])
                ->map(fn ($parte): string => trim((string) $parte))
                ->filter()
                ->implode(' ') ?: ($persona->nombre_completo ?: '—'),
            'cargo' => null,
            'direccion' => null,
            'pinReloj' => trim((string) $persona->pinReloj),
            'conContrato' => null,
            'origen' => 'siat',
        ];
    }
}

<?php

namespace App\Services;

use App\Exceptions\MamoreException;
use App\Models\Persona;
use Illuminate\Support\Facades\Cache;

/**
 * Resuelve el nombre de un funcionario a partir de su CI: primero en la API
 * externa de Mamoré y, si esa persona no está ahí (o la API no está
 * configurada / falla), en la base local MySQL (`personas`).
 *
 * Lo usan los listados que muestran una columna «Funcionario» a partir de un CI
 * suelto (marcaciones, licencias). Solo consulta los CI distintos de la página
 * y cachea la respuesta de Mamoré por un día, para no golpear la API fila por
 * fila.
 */
class ResolutorNombres
{
    public function __construct(private MamoreClient $mamore) {}

    /**
     * Nombre de cada CI, con Mamoré primero y la base local como respaldo.
     *
     * @param  iterable<mixed>  $cis
     * @return array<string, ?string> ci => nombre (o null si no está en ninguno)
     */
    public function porCi(iterable $cis): array
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
            ->whereIn('ci', $cis->all())
            ->get()
            ->mapWithKeys(fn (Persona $persona): array => [trim((string) $persona->ci) => $persona->nombre_completo]);

        $usarMamore = $this->mamore->configurado();
        $nombres = [];

        foreach ($cis as $ci) {
            $nombre = $usarMamore ? $this->nombreMamore($ci) : '';

            if ($nombre === '') {
                $nombre = (string) ($locales->get($ci) ?? '');
            }

            $nombres[$ci] = $nombre !== '' ? $nombre : null;
        }

        return $nombres;
    }

    /**
     * Nombre de una persona en Mamoré por CI, cacheado por un día. Devuelve ''
     * si no existe (404). Un fallo transitorio de la API no se cachea (se
     * reintenta luego) y también devuelve ''.
     */
    private function nombreMamore(string $ci): string
    {
        $clave = 'mamore.nombre.'.$ci;
        $cacheado = Cache::get($clave);

        if ($cacheado !== null) {
            return $cacheado;
        }

        try {
            $persona = $this->mamore->personByCi($ci);
        } catch (MamoreException) {
            return '';
        }

        $nombre = $persona ? trim((string) ($persona['full_name'] ?? '')) : '';

        Cache::put($clave, $nombre, now()->addDay());

        return $nombre;
    }
}

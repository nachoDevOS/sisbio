<?php

namespace App\Services;

use App\Exceptions\MamoreException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP de la API externa de Datos Personales «Mamoré» (solo lectura).
 * Consulta la lista y el detalle de personas con el header X-API-KEY.
 *
 * La API tiene un único listado: `/people` devuelve todas las personas, y cada
 * una trae su contrato firmado en la clave `contrato` (o `null` si no tiene).
 */
class MamoreClient
{
    /**
     * ¿Están cargadas la URL y la clave para poder consultar la API?
     */
    public function configurado(): bool
    {
        return filled(config('services.mamore.url')) && filled(config('services.mamore.key'));
    }

    /**
     * Lista paginada de personas (con búsqueda). Devuelve el JSON tal cual
     * (`data`, `meta`, `links`).
     *
     * El filtro `$contrato` («todos», «con» o «sin») lo resuelve la propia API
     * con el parámetro `?contrato=`, así que la paginación sigue siendo la suya.
     *
     * @return array{data?: array<int, array<string, mixed>>, meta?: array<string, mixed>, links?: array<string, mixed>}
     */
    public function people(int $page, int $limit, string $search = '', string $contrato = 'todos'): array
    {
        $parametros = ['page' => $page, 'limit' => $limit];

        if ($search !== '') {
            $parametros['search'] = $search;
        }

        if (in_array($contrato, ['con', 'sin'], true)) {
            $parametros['contrato'] = $contrato;
        }

        return $this->pedir('/people', $parametros);
    }

    /**
     * Detalle de una persona por su cédula. `null` si no existe (404).
     *
     * @return array<string, mixed>|null
     */
    public function personByCi(string $ci): ?array
    {
        try {
            $respuesta = $this->http()->get('/people/ci/'.rawurlencode($ci));
        } catch (ConnectionException) {
            throw new MamoreException('No se pudo conectar con la API de Mamoré.');
        }

        if ($respuesta->status() === 404) {
            return null;
        }

        if ($respuesta->failed()) {
            throw new MamoreException($this->motivo($respuesta->status()));
        }

        return $respuesta->json('data');
    }

    /**
     * GET a la API traduciendo fallos de red y estados de error a MamoreException.
     *
     * @param  array<string, mixed>  $parametros
     * @return array<string, mixed>
     */
    private function pedir(string $ruta, array $parametros): array
    {
        try {
            $respuesta = $this->http()->get($ruta, $parametros);
        } catch (ConnectionException) {
            throw new MamoreException('No se pudo conectar con la API de Mamoré.');
        }

        if ($respuesta->failed()) {
            throw new MamoreException($this->motivo($respuesta->status()));
        }

        return $respuesta->json();
    }

    private function http(): PendingRequest
    {
        if (! $this->configurado()) {
            throw new MamoreException('La API de Mamoré no está configurada (MAMORE_API_URL / MAMORE_API_KEY en el .env).');
        }

        return Http::baseUrl(rtrim((string) config('services.mamore.url'), '/'))
            ->withHeaders(['X-API-KEY' => config('services.mamore.key')])
            ->acceptJson()
            ->timeout(10);
    }

    private function motivo(int $status): string
    {
        return match ($status) {
            401 => 'La clave de la API de Mamoré es inválida.',
            503 => 'La API de Mamoré no tiene la clave configurada en su servidor.',
            default => "La API de Mamoré respondió con un error ({$status}).",
        };
    }
}

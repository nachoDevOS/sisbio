<?php

namespace App\Services;

use App\Exceptions\DeviceServiceException;
use App\Models\Equipo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP hacia el microservicio de dispositivos (Python/FastAPI).
 *
 * Es el único punto por el que Laravel se comunica con los equipos biométricos.
 * No abre sockets: traduce cada operación a una petición HTTP autenticada con el
 * token compartido, y convierte los errores del microservicio en excepciones
 * con mensajes claros para el usuario.
 */
class DeviceService
{
    /**
     * URL base del microservicio, sin barra final.
     */
    private readonly string $baseUrl;

    /**
     * Token compartido enviado en el encabezado X-Auth-Token.
     */
    private readonly ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.device_service.url'), '/');
        $this->token = config('services.device_service.token');
    }

    /**
     * Trae la información de identificación de un equipo (nombre, serial,
     * plataforma, firmware y firma de algoritmo).
     *
     * @return array<string, mixed>
     *
     * @throws DeviceServiceException
     */
    public function info(Equipo $equipo): array
    {
        return $this->get('/device/info', $equipo);
    }

    /**
     * Trae la lista de usuarios registrados en un equipo.
     *
     * @return array<string, mixed>
     *
     * @throws DeviceServiceException
     */
    public function users(Equipo $equipo): array
    {
        return $this->get('/device/users', $equipo);
    }

    /**
     * Trae las marcaciones (registros de asistencia) guardadas en el equipo,
     * de la más reciente a la más antigua.
     *
     * @return array<string, mixed>
     *
     * @throws DeviceServiceException
     */
    public function attendance(Equipo $equipo): array
    {
        return $this->get('/device/attendance', $equipo);
    }

    /**
     * Ejecuta un GET autenticado contra el microservicio para un equipo dado.
     *
     * @return array<string, mixed>
     *
     * @throws DeviceServiceException
     */
    private function get(string $path, Equipo $equipo): array
    {
        try {
            $response = Http::withHeaders(['X-Auth-Token' => $this->token])
                ->connectTimeout(5) // El microservicio debe estar arriba en la red interna.
                ->timeout(60) // Leer usuarios + marcaciones de equipos con historial largo puede tardar.
                ->get($this->baseUrl.$path, [
                    'ip' => $equipo->ip,
                    'port' => $equipo->puerto,
                    'password' => $equipo->comm_key,
                ]);
        } catch (ConnectionException $e) {
            // No se pudo ni contactar al microservicio (apagado o URL mal configurada).
            throw new DeviceServiceException(
                'No se pudo contactar al microservicio de dispositivos. Verifica que esté encendido.',
                previous: $e,
            );
        }

        if ($response->failed()) {
            // El microservicio respondió con error (ej. 503 equipo caído, 401 token malo).
            $detalle = $response->json('detail') ?? 'El microservicio devolvió un error inesperado.';

            throw new DeviceServiceException($detalle);
        }

        return $response->json();
    }
}

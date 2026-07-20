<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error al comunicarse con el microservicio de dispositivos o con un equipo.
 *
 * Lleva un mensaje en español apto para mostrar directamente al usuario
 * (mensaje flash o notificación).
 */
class DeviceServiceException extends RuntimeException {}

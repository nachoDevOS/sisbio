<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error al consultar la API externa de Datos Personales «Mamoré».
 *
 * Lleva un mensaje en español apto para mostrar directamente al usuario
 * (aviso en pantalla o notificación).
 */
class MamoreException extends RuntimeException {}

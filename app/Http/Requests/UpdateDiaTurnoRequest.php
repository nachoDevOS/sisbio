<?php

namespace App\Http\Requests;

/**
 * Mismas reglas que el alta: el código del turno (IdTurno) es la clave y no se
 * edita, así que no hay diferencias respecto a StoreDiaTurnoRequest.
 */
class UpdateDiaTurnoRequest extends StoreDiaTurnoRequest {}

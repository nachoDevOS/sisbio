<?php

namespace App\Models;

use App\Traits\RegistersUserEvents;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Rol del sistema (Spatie permission), extendido con eliminación lógica.
 *
 * Todo el borrado del sistema es lógico: destroy() solo marca `deleted_at` y
 * el rol desaparece de los listados y de las consultas de permisos sin salir
 * de la base. La conexión con Spatie se hace vía `config('permission.models.role')`.
 *
 * RegistersUserEvents deja además quién lo eliminó y el motivo que se escribió
 * en el modal de eliminación (`deleteUser_id` / `deleteObservacion`).
 */
class Role extends SpatieRole
{
    use RegistersUserEvents, SoftDeletes;
}

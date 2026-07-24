<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Rol del sistema (Spatie permission), extendido con eliminación lógica.
 *
 * Todo el borrado del sistema es lógico: destroy() solo marca `deleted_at` y
 * el rol desaparece de los listados y de las consultas de permisos sin salir
 * de la base. La conexión con Spatie se hace vía `config('permission.models.role')`.
 */
class Role extends SpatieRole
{
    use SoftDeletes;
}

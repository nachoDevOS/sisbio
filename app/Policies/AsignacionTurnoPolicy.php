<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AsignacionTurno;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorización del listado de turnos asignados (tabla `asignacion_turnos`).
 * Reutiliza los permisos de los turnos (DiaTurno): quien puede ver los turnos
 * puede ver a quién están asignados, sin sumar permisos nuevos a los roles.
 */
class AsignacionTurnoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DiaTurno');
    }

    public function view(AuthUser $authUser, AsignacionTurno $asignacion): bool
    {
        return $authUser->can('View:DiaTurno');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DiaTurno');
    }

    /**
     * Concluir una asignación (ponerle fecha de fin) es editarla.
     */
    public function update(AuthUser $authUser, AsignacionTurno $asignacion): bool
    {
        return $authUser->can('Update:DiaTurno');
    }

    public function delete(AuthUser $authUser, AsignacionTurno $asignacion): bool
    {
        return $authUser->can('Delete:DiaTurno');
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Turno;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorización de los horarios locales (MySQL, tabla `turnos`). Usa los mismos
 * permisos que la policy legada (ViewAny:DiaTurno, etc.), así los roles no
 * cambian al pasar la vista de la conexión `sia` a la base local.
 */
class TurnoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DiaTurno');
    }

    public function view(AuthUser $authUser, Turno $turno): bool
    {
        return $authUser->can('View:DiaTurno');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DiaTurno');
    }

    public function update(AuthUser $authUser, Turno $turno): bool
    {
        return $authUser->can('Update:DiaTurno');
    }

    public function delete(AuthUser $authUser, Turno $turno): bool
    {
        return $authUser->can('Delete:DiaTurno');
    }
}

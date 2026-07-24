<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Asistencia;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorización de las marcaciones locales (MySQL). Usa los mismos permisos que
 * la policy legada (ViewAny:Asistencia, etc.), así los roles no cambian al pasar
 * la vista de la conexión `sia` a la base local.
 */
class AsistenciaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Asistencia');
    }

    public function view(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('View:Asistencia');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Asistencia');
    }

    public function update(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('Update:Asistencia');
    }

    public function delete(AuthUser $authUser, Asistencia $asistencia): bool
    {
        return $authUser->can('Delete:Asistencia');
    }
}

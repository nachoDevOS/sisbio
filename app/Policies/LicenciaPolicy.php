<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Licencia;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorización de las licencias/permisos de personal (MySQL, tabla `licencias`).
 * Usa la convención de permisos del sistema (ViewAny:Licencia, etc.).
 */
class LicenciaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Licencia');
    }

    public function view(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('View:Licencia');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Licencia');
    }

    public function update(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('Update:Licencia');
    }

    public function delete(AuthUser $authUser, Licencia $licencia): bool
    {
        return $authUser->can('Delete:Licencia');
    }
}

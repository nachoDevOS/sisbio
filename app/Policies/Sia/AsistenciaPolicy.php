<?php

declare(strict_types=1);

namespace App\Policies\Sia;

use App\Models\Sia\Asistencia;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

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

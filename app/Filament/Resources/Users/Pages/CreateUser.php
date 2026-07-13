<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Sin botón "crear y crear otro"; al crear se vuelve al listado
     * (redirect global en AdminPanelProvider::resourceCreatePageRedirect).
     */
    protected static bool $canCreateAnother = false;
}

<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Filament\Resources\Equipos\EquipoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipo extends CreateRecord
{
    protected static string $resource = EquipoResource::class;

    /**
     * Sin botón "crear y crear otro"; al crear se vuelve al listado
     * (redirect global en AdminPanelProvider::resourceCreatePageRedirect).
     */
    protected static bool $canCreateAnother = false;
}

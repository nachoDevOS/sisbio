<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePersona extends CreateRecord
{
    protected static string $resource = PersonaResource::class;

    /**
     * Sin botón "crear y crear otro"; al crear se vuelve al listado
     * (redirect global en AdminPanelProvider::resourceCreatePageRedirect).
     */
    protected static bool $canCreateAnother = false;

    /**
     * La sección "Control de asistencia" está deshabilitada y sus campos no
     * se dehidratan, pero MarcaDirecta es NOT NULL sin default en el SQL
     * Server del SIA: el INSERT siempre debe mandarla.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['MarcaDirecta'] = false;

        return $data;
    }
}

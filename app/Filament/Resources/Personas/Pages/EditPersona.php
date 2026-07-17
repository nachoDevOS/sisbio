<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Edición de un funcionario del SIA con el mismo formulario del alta.
 *
 * El carnet (clave primaria) y la sección "Control de asistencia" están
 * deshabilitados: no se dehidratan, así que el UPDATE nunca los toca.
 */
class EditPersona extends EditRecord
{
    protected static string $resource = PersonaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

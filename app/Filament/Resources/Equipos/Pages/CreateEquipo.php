<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Filament\Resources\Equipos\EquipoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipo extends CreateRecord
{
    protected static string $resource = EquipoResource::class;

    /**
     * Tras crear un equipo, vuelve al listado (no se queda en el formulario).
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

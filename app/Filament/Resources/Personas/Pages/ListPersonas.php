<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Resources\Pages\ListRecords;

class ListPersonas extends ListRecords
{
    protected static string $resource = PersonaResource::class;
}

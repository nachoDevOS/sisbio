<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Ficha de detalle de un funcionario dentro del panel, con el infolist
 * definido en PersonaResource (diseño nativo de Filament).
 */
class VerPersona extends ViewRecord
{
    protected static string $resource = PersonaResource::class;

    public function getTitle(): string
    {
        return $this->record->nombre_completo ?: 'CI '.trim($this->record->IdPersona);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

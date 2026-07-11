<?php

namespace App\Filament\Resources\Marcaciones\Pages;

use App\Filament\Resources\Marcaciones\MarcacionResource;
use App\Models\Sia\Asistencia;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListMarcaciones extends ListRecords
{
    protected static string $resource = MarcacionResource::class;

    /**
     * La tabla Asistencia tiene clave primaria compuesta (IdPersona, Fecha,
     * Hora), así que la clave de fila se arma con esos tres campos.
     */
    public function getTableRecordKey(Model|array $record): string
    {
        if ($record instanceof Asistencia) {
            return implode('|', [
                trim((string) $record->IdPersona),
                $record->Fecha?->format('Ymd') ?? '',
                $record->Hora?->format('His') ?? '',
            ]);
        }

        return parent::getTableRecordKey($record);
    }
}

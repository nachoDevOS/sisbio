<?php

namespace App\Filament\Resources\Marcaciones;

use App\Filament\Resources\Marcaciones\Pages\ListMarcaciones;
use App\Filament\Resources\Marcaciones\Tables\MarcacionesTable;
use App\Models\Sia\Asistencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Listado de solo lectura de las marcaciones del sistema SIA
 * (SQL Server 2008 R2 remoto). El panel nunca escribe sobre esa base.
 */
class MarcacionResource extends Resource
{
    protected static ?string $model = Asistencia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Asistencia SIA';

    protected static ?string $modelLabel = 'marcación';

    protected static ?string $pluralModelLabel = 'marcaciones';

    protected static ?string $navigationLabel = 'Marcaciones';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return MarcacionesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarcaciones::route('/'),
        ];
    }
}

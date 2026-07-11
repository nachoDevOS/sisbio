<?php

namespace App\Filament\Resources\Personas;

use App\Filament\Resources\Personas\Pages\ListPersonas;
use App\Filament\Resources\Personas\Tables\PersonasTable;
use App\Models\Sia\Persona;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Listado de solo lectura de los funcionarios registrados en el SIA
 * (SQL Server 2008 R2 remoto). El panel nunca escribe sobre esa base.
 */
class PersonaResource extends Resource
{
    protected static ?string $model = Persona::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Asistencia SIA';

    protected static ?string $modelLabel = 'funcionario';

    protected static ?string $pluralModelLabel = 'funcionarios';

    protected static ?string $navigationLabel = 'Funcionarios';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return PersonasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonas::route('/'),
        ];
    }
}

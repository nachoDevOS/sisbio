<?php

namespace App\Filament\Resources\Personas;

use App\Filament\Resources\Personas\Pages\CreatePersona;
use App\Filament\Resources\Personas\Pages\EditPersona;
use App\Filament\Resources\Personas\Pages\ListPersonas;
use App\Filament\Resources\Personas\Pages\MarcacionesPersona;
use App\Filament\Resources\Personas\Pages\VerPersona;
use App\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Filament\Resources\Personas\Schemas\PersonaInfolist;
use App\Filament\Resources\Personas\Tables\PersonasTable;
use App\Models\Sia\Persona;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Funcionarios registrados en el SIA (SQL Server 2008 R2 remoto).
 *
 * Lista, da de alta y edita la tabla legada Personas con el mismo
 * formulario que el sistema de escritorio del SIA. Sin borrado: eso
 * sigue siendo terreno del sistema legado.
 */
class PersonaResource extends Resource
{
    protected static ?string $model = Persona::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Asistencia SIA';

    protected static ?string $modelLabel = 'funcionario';

    protected static ?string $pluralModelLabel = 'funcionarios';

    protected static ?string $navigationLabel = 'Funcionarios';

    public static function form(Schema $schema): Schema
    {
        return PersonaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PersonaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonas::route('/'),
            'create' => CreatePersona::route('/create'),
            'view' => VerPersona::route('/{record}'),
            'marcaciones' => MarcacionesPersona::route('/{record}/marcaciones'),
            'edit' => EditPersona::route('/{record}/edit'),
        ];
    }
}

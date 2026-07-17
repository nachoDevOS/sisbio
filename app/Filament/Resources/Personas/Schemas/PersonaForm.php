<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Models\Sia\Profesion;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonaForm
{
    /**
     * Formulario de alta y edición de funcionarios sobre la tabla Personas
     * del SIA. Replica el formulario "Añadir nuevo registro" del sistema de
     * escritorio: mismas secciones y campos, para que ambos sistemas
     * escriban registros equivalentes en la base compartida.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('IdPersona')
                            ->label('Nro. carnet de identidad')
                            ->required()
                            ->maxLength(12)
                            ->unique(ignoreRecord: true)
                            // El carnet es la clave primaria del registro
                            // legado: nunca se cambia una vez creado.
                            ->disabledOn('edit')
                            ->dehydrateStateUsing(fn (string $state): string => trim($state)),
                        TextInput::make('OrigenId')
                            ->label('Expedido en')
                            ->maxLength(3)
                            ->helperText('Sigla del departamento: LP, CB, SC, OR, PT, TJ, CH, BE, PD'),
                        TextInput::make('Paterno')
                            ->label('Apellido paterno')
                            ->required()
                            ->maxLength(25),
                        TextInput::make('Materno')
                            ->label('Apellido materno')
                            ->maxLength(25),
                        TextInput::make('Nombres')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(35),
                        DatePicker::make('FechaNacimiento')
                            ->label('Fecha de nacimiento')
                            ->required()
                            ->maxDate(now()),
                        TextInput::make('LugarNacimiento')
                            ->label('Lugar de nacimiento')
                            ->maxLength(25),
                        Select::make('Sexo')
                            ->label('Sexo')
                            ->required()
                            ->options([
                                'F' => 'Femenino',
                                'M' => 'Masculino',
                            ]),
                        Select::make('EstadoCivil')
                            ->label('Estado civil')
                            ->required()
                            ->options([
                                'S' => 'Soltero(a)',
                                'C' => 'Casado(a)',
                                'D' => 'Divorciado(a)',
                                'V' => 'Viudo(a)',
                            ]),
                    ]),
                Section::make('Estudios')
                    ->columns(2)
                    ->schema([
                        Select::make('CodigoProfesion')
                            ->label('Profesión')
                            ->required()
                            ->searchable()
                            ->default('00')
                            ->options(fn (): array => Profesion::query()
                                ->orderBy('NombreProfesion')
                                ->pluck('NombreProfesion', 'CodigoProfesion')
                                ->all()),
                        Select::make('NivelEstudio')
                            ->label('Nivel')
                            ->options([
                                'Primarios' => 'Primarios',
                                'Secundarios' => 'Secundarios',
                                'Bachiller' => 'Bachiller',
                                'Técnico Medio' => 'Técnico Medio',
                                'Técnico Superior' => 'Técnico Superior',
                                'Egresado Univ.' => 'Egresado Univ.',
                                'Profesional' => 'Profesional',
                                'Diplomado' => 'Diplomado',
                                'Masterado' => 'Masterado',
                                'Doctorado' => 'Doctorado',
                                'PHD' => 'PHD',
                            ]),
                    ]),
                Section::make('Contactos')
                    ->columns(2)
                    ->schema([
                        TextInput::make('Telefono')
                            ->label('Teléfonos')
                            ->maxLength(20),
                        TextInput::make('Direccion')
                            ->label('Dirección')
                            ->maxLength(40),
                        TextInput::make('CorreoE')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(40),
                    ]),
                Section::make('Control de asistencia')
                    ->columns(2)
                    // Deshabilitada por ahora: el PIN y la marcación con
                    // contraseña se siguen gestionando desde el sistema
                    // de escritorio del SIA.
                    ->disabled()
                    ->schema([
                        TextInput::make('PinReloj')
                            ->label('PIN reloj lector de huellas')
                            ->maxLength(10),
                        Toggle::make('MarcaDirecta')
                            ->label('Puede marcar con contraseña')
                            ->default(false),
                    ]),
            ]);
    }
}

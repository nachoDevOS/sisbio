<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonaInfolist
{
    /**
     * Ficha de solo lectura de un funcionario del SIA, con las mismas
     * secciones del formulario. Los campos char() legados llegan con relleno
     * de espacios: los entries aplican trim() al mostrar.
     */
    public static function configure(Schema $schema): Schema
    {
        $trim = fn (?string $state): string => trim((string) $state) ?: '—';

        return $schema
            ->components([
                Section::make('Datos personales')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('IdPersona')
                            ->label('Nro. carnet de identidad')
                            ->formatStateUsing($trim),
                        TextEntry::make('OrigenId')
                            ->label('Expedido en')
                            ->formatStateUsing($trim),
                        TextEntry::make('Paterno')
                            ->label('Apellido paterno')
                            ->formatStateUsing($trim),
                        TextEntry::make('Materno')
                            ->label('Apellido materno')
                            ->formatStateUsing($trim),
                        TextEntry::make('Nombres')
                            ->label('Nombres')
                            ->formatStateUsing($trim),
                        TextEntry::make('FechaNacimiento')
                            ->label('Fecha de nacimiento')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('LugarNacimiento')
                            ->label('Lugar de nacimiento')
                            ->formatStateUsing($trim),
                        TextEntry::make('Sexo')
                            ->label('Sexo')
                            ->formatStateUsing(fn (?string $state): string => match (trim((string) $state)) {
                                'F' => 'Femenino',
                                'M' => 'Masculino',
                                default => '—',
                            }),
                        TextEntry::make('EstadoCivil')
                            ->label('Estado civil')
                            ->formatStateUsing(fn (?string $state): string => match (trim((string) $state)) {
                                'S' => 'Soltero(a)',
                                'C' => 'Casado(a)',
                                'D' => 'Divorciado(a)',
                                'V' => 'Viudo(a)',
                                default => '—',
                            }),
                    ]),
                Section::make('Estudios')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('profesion.NombreProfesion')
                            ->label('Profesión')
                            ->formatStateUsing($trim),
                        TextEntry::make('NivelEstudio')
                            ->label('Nivel')
                            ->formatStateUsing($trim),
                    ]),
                Section::make('Contactos')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('Telefono')
                            ->label('Teléfonos')
                            ->formatStateUsing($trim),
                        TextEntry::make('Direccion')
                            ->label('Dirección')
                            ->formatStateUsing($trim),
                        TextEntry::make('CorreoE')
                            ->label('E-mail')
                            ->formatStateUsing($trim)
                            ->copyable(),
                    ]),
                Section::make('Control de asistencia')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('PinReloj')
                            ->label('PIN reloj lector de huellas')
                            ->formatStateUsing(fn (?string $state): string => trim((string) $state) ?: 'Sin PIN'),
                        TextEntry::make('MarcaDirecta')
                            ->label('Puede marcar con contraseña')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Sí' : 'No')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ]),
            ]);
    }
}

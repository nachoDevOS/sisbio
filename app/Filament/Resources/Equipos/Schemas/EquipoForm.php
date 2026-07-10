<?php

namespace App\Filament\Resources\Equipos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EquipoForm
{
    /**
     * Formulario de alta/edición de un equipo biométrico.
     *
     * Solo expone los datos que registra el usuario. Los campos de estado
     * (algoritmo, en_linea, ultima_sync) los actualiza automáticamente la
     * acción "Probar conexión", por eso no aparecen aquí.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('ip')
                    ->label('Dirección IP')
                    ->helperText('IP del equipo en la red LAN, ej. 192.168.1.201')
                    ->required()
                    ->rule('ip'), // Valida que sea una IPv4/IPv6 válida
                TextInput::make('puerto')
                    ->label('Puerto')
                    ->helperText('Puerto TCP del protocolo ZKTeco (4370 por defecto)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(65535)
                    ->default(4370),
                TextInput::make('comm_key')
                    ->label('COMM key')
                    ->helperText('Clave de comunicación del equipo (0 si no tiene)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('ubicacion')
                    ->label('Ubicación')
                    ->helperText('Dónde está físicamente, ej. "Puerta principal"')
                    ->maxLength(255),
                Toggle::make('es_master')
                    ->label('Equipo maestro')
                    ->helperText('El maestro es el origen de las huellas que se replican al resto')
                    ->default(false),
                Toggle::make('activo')
                    ->label('Activo')
                    ->helperText('Si participa en la sincronización')
                    ->default(true),
            ]);
    }
}

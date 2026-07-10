<?php

namespace App\Models;

use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa un equipo biométrico ZKTeco en la red LAN.
 *
 * Cada registro es un dispositivo físico con el que el microservicio Python
 * se comunica por TCP (puerto 4370 por defecto). El panel de Filament nunca
 * habla directo con el equipo: solo lee/escribe estas columnas y delega la
 * comunicación real al microservicio.
 */
class Equipo extends Model
{
    /** @use HasFactory<EquipoFactory> */
    use HasFactory;

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'ip',
        'puerto',
        'comm_key',
        'ubicacion',
        'algoritmo',
        'es_master',
        'en_linea',
        'ultima_sync',
        'activo',
    ];

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'puerto' => 'integer',
            'comm_key' => 'integer',
            'es_master' => 'boolean',
            'en_linea' => 'boolean',
            'activo' => 'boolean',
            'ultima_sync' => 'datetime',
        ];
    }
}

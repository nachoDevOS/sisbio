<?php

namespace App\Models;

use App\Traits\RegistersUserEvents;
use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Representa un equipo biométrico ZKTeco en la red LAN.
 *
 * Cada registro es un dispositivo físico con el que el microservicio Python
 * se comunica por TCP (puerto 4370 por defecto). Laravel nunca habla directo
 * con el equipo: solo lee/escribe estas columnas y delega la comunicación
 * real al microservicio.
 *
 * Usa eliminación lógica (SoftDeletes): destroy() solo marca deleted_at y el
 * equipo desaparece de los listados sin borrarse de la base.
 */
class Equipo extends Model
{
    /** @use HasFactory<EquipoFactory> */
    use HasFactory, RegistersUserEvents, SoftDeletes;

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
        'observacion',
        'estado',
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

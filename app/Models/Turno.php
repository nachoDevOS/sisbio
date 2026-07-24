<?php

namespace App\Models;

use Database\Factories\TurnoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Horario (turno) en la base local (MySQL), migrado desde «DiaTurnos» del SIA.
 *
 * Conexión por defecto, con id propio, timestamps y eliminación lógica. Las
 * horas van como datetime sobre la fecha base 1899-12-30 (solo importa la hora),
 * como el SIA real.
 */
class Turno extends Model
{
    /** @use HasFactory<TurnoFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Días de la semana según el número que guarda la columna `dia`
     * (1 = Domingo … 7 = Sábado, igual que DATEPART(dw) por defecto en el SIA).
     *
     * @var array<int, string>
     */
    public const DIAS = [
        1 => 'Domingo',
        2 => 'Lunes',
        3 => 'Martes',
        4 => 'Miércoles',
        5 => 'Jueves',
        6 => 'Viernes',
        7 => 'Sábado',
    ];

    protected $table = 'turnos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'idTurno',
        'dia',
        'nombreTurno',
        'hEntrada',
        'hSalida',
        'hTolerancia',
        'eMinima',
        'eMaxima',
        'sMinima',
        'sMaxima',
        'sTolerancia',
        'hTrabajadas',
        'siguienteDia',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hEntrada' => 'datetime',
            'hSalida' => 'datetime',
            'hTolerancia' => 'datetime',
            'eMinima' => 'datetime',
            'eMaxima' => 'datetime',
            'sMinima' => 'datetime',
            'sMaxima' => 'datetime',
            'sTolerancia' => 'datetime',
            'hTrabajadas' => 'decimal:2',
            'siguienteDia' => 'boolean',
        ];
    }

    /**
     * Nombre legible del día de la semana del turno.
     */
    public function getNombreDiaAttribute(): string
    {
        return self::DIAS[(int) $this->dia] ?? '—';
    }

    /**
     * Ordena por día de la semana y luego por nombre del turno.
     */
    public function scopeOrdenado(Builder $query): Builder
    {
        return $query->orderBy('dia')->orderBy('nombreTurno');
    }
}

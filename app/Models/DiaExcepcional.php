<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Día excepcional (feriado, tolerancia, motivo de inasistencia) en la base
 * local (MySQL), migrado desde «Calendario» del SIA.
 *
 * Conexión por defecto, con id propio, timestamps y eliminación lógica. Una
 * fila por fecha.
 */
class DiaExcepcional extends Model
{
    use SoftDeletes;

    protected $table = 'dias_excepcionales';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fecha',
        'motivoInasistencia',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use App\Traits\RegistersUserEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Asignación de turno en la base local (MySQL), migrada desde «AsignacionTurnos»
 * del SIA: el turno asignado a un funcionario en un rango de fechas.
 *
 * Conexión por defecto, con id propio, timestamps y eliminación lógica. El
 * carnet vive en `ci` (en el SIA era IdPersona). Se conserva `idTurno` (código
 * del SIA) y se agrega la FK `turno_id` → `turnos.id`, resuelta al copiar.
 */
class AsignacionTurno extends Model
{
    use RegistersUserEvents, SoftDeletes;

    protected $table = 'asignacion_turnos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ci',
        'idTurno',
        'turno_id',
        'desde',
        'hasta',
        'observacion',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'desde' => 'datetime',
            'hasta' => 'datetime',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'ci', 'ci');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }
}

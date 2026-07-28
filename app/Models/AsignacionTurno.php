<?php

namespace App\Models;

use App\Traits\RegistersUserEvents;
use Carbon\CarbonInterface;
use Database\Factories\AsignacionTurnoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    /** @use HasFactory<AsignacionTurnoFactory> */
    use HasFactory, RegistersUserEvents, SoftDeletes;

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

    /**
     * El turno asignado. Se relaciona por `turno_id` (la FK real): `idTurno` es
     * solo el código histórico que traía el SIA y no sirve para vincular.
     */
    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    /**
     * Filtra por CI, por nombre del funcionario o por nombre del turno. Cada
     * palabra del texto debe aparecer en alguno de los tres.
     */
    public function scopeBuscar(Builder $query, string $texto): Builder
    {
        foreach (Persona::terminos($texto) as $termino) {
            $query->where(fn (Builder $sub) => $sub
                ->where('ci', 'like', "%{$termino}%")
                ->orWhereHas('persona', fn (Builder $persona) => $persona
                    ->where(fn (Builder $nombre) => $nombre->coincideNombre($termino)))
                ->orWhereHas('turno', fn (Builder $turno) => $turno
                    ->where('nombreTurno', 'like', "%{$termino}%")));
        }

        return $query;
    }

    /**
     * Asignaciones que cubren la fecha dada (`desde` ≤ fecha ≤ `hasta`).
     */
    public function scopeVigenteEn(Builder $query, CarbonInterface $fecha): Builder
    {
        return $query->whereDate('desde', '<=', $fecha)->whereDate('hasta', '>=', $fecha);
    }

    /**
     * Situación de la asignación respecto de hoy: `vigente`, `vencida` (ya
     * terminó) o `futura` (todavía no empieza).
     */
    public function getSituacionAttribute(): string
    {
        if ($this->hasta?->isBefore(today())) {
            return 'vencida';
        }

        return $this->desde?->isAfter(today()) ? 'futura' : 'vigente';
    }
}

<?php

namespace App\Models\Sia;

use Database\Factories\Sia\DiaTurnoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Horario (turno) del SIA: define para un día de la semana las horas de
 * entrada/salida con sus tolerancias y rangos mínimo/máximo, más las horas
 * trabajadas. Tabla legada `DiaTurnos` (SQL Server 2008 R2 remoto), compartida
 * con el «Administrador de horarios» del sistema de escritorio.
 *
 * Las horas se guardan como datetime sobre la fecha base 1899-12-30 (solo
 * importa la parte de hora), igual que `Asistencia::$Hora`.
 */
class DiaTurno extends Model
{
    /** @use HasFactory<DiaTurnoFactory> */
    use HasFactory;

    /**
     * Días de la semana según el número que guarda la columna `Dia`
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

    protected $connection = 'sia';

    protected $table = 'DiaTurnos';

    protected $primaryKey = 'IdTurno';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * Mismo formato ISO con "T" que el resto de modelos del SIA, para que
     * SQL Server 2008 lo interprete igual con cualquier SET LANGUAGE.
     */
    protected $dateFormat = 'Y-m-d\TH:i:s';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'IdTurno',
        'Dia',
        'NombreTurno',
        'HEntrada',
        'HSalida',
        'HTolerancia',
        'EMinima',
        'EMaxima',
        'SMinima',
        'SMaxima',
        'STolerancia',
        'HTrabajadas',
        'SiguienteDia',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'HEntrada' => 'datetime',
            'HSalida' => 'datetime',
            'HTolerancia' => 'datetime',
            'EMinima' => 'datetime',
            'EMaxima' => 'datetime',
            'SMinima' => 'datetime',
            'SMaxima' => 'datetime',
            'STolerancia' => 'datetime',
            'HTrabajadas' => 'decimal:2',
            'SiguienteDia' => 'boolean',
        ];
    }

    /**
     * URLs con el código de turno sin el relleno del char(3).
     */
    public function getRouteKey(): string
    {
        return trim((string) $this->getAttribute($this->getRouteKeyName()));
    }

    /**
     * Resuelve el binding probando el código tal cual y rellenado a 3 (el
     * char(3) del SQL Server ignora los espacios finales; el sqlite de los
     * tests compara exacto).
     */
    public function resolveRouteBinding($value, $field = null): ?DiaTurno
    {
        $columna = $field ?? $this->getRouteKeyName();

        return $this->newQuery()
            ->where($columna, (string) $value)
            ->orWhere($columna, str_pad((string) $value, 3))
            ->first();
    }

    /**
     * Nombre legible del día de la semana del turno.
     */
    public function getNombreDiaAttribute(): string
    {
        return self::DIAS[(int) $this->Dia] ?? '—';
    }

    /**
     * Ordena por día de la semana y luego por nombre del turno.
     */
    public function scopeOrdenado(Builder $query): Builder
    {
        return $query->orderBy('Dia')->orderBy('NombreTurno');
    }
}

<?php

namespace App\Models\Sia;

use Database\Factories\Sia\AsistenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marcación de asistencia registrada en el sistema SIA.
 *
 * Tabla legada de solo lectura con clave primaria compuesta
 * (IdPersona, Fecha, Hora), por eso $primaryKey es null y el listado de
 * Filament define su propia clave de fila. `Fecha` guarda solo la fecha
 * (medianoche) y `Hora` solo la hora (sobre la fecha base 1899-12-30,
 * patrón clásico de SQL Server 2008).
 */
class Asistencia extends Model
{
    /** @use HasFactory<AsistenciaFactory> */
    use HasFactory;

    public const TIPO_RELOJ = 'R';

    public const TIPO_MANUAL = 'M';

    public const TIPO_A = 'A';

    protected $connection = 'sia';

    protected $table = 'Asistencia';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    /**
     * Casts de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'Fecha' => 'datetime',
            'Hora' => 'datetime',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'IdPersona', 'IdPersona');
    }
}

<?php

namespace App\Models\Sia;

use Database\Factories\Sia\AsistenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marcación de asistencia registrada en el sistema SIA.
 *
 * Tabla legada con clave primaria compuesta (IdPersona, Fecha, Hora), por eso
 * $primaryKey es null. Solo se escribe desde MarcacionController::importar()
 * (import de CSV); el resto de la app la usa de solo lectura. `Fecha` guarda
 * solo la fecha (medianoche) y `Hora` solo la hora (sobre la fecha base
 * 1899-12-30, patrón clásico de SQL Server 2008).
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
     * Formato ISO 8601 con "T": el único que SQL Server 2008 interpreta igual
     * con cualquier SET LANGUAGE. Con "Y-m-d H:i:s" y el login en español, el
     * servidor lee año-día-mes y revienta con fechas válidas (ej. 2026-07-16
     * lo toma como día 07 mes 16). Mismo motivo que en Persona::$dateFormat.
     */
    protected $dateFormat = 'Y-m-d\TH:i:s';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'IdPersona',
        'Fecha',
        'Hora',
        'Tipo',
    ];

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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Marcación en la base local (MySQL), migrada desde «Asistencia» del SIA.
 *
 * Usa la conexión por defecto (MySQL), con id propio, timestamps y eliminación
 * lógica. El carnet vive en la columna `ci` (en el SIA era IdPersona). `hora`
 * guarda solo la hora sobre la fecha base 1899-12-30, como el SIA real.
 */
class Asistencia extends Model
{
    use SoftDeletes;

    public const TIPO_RELOJ = 'R';

    public const TIPO_MANUAL = 'M';

    public const TIPO_A = 'A';

    protected $table = 'asistencias';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ci',
        'fecha',
        'hora',
        'tipo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'hora' => 'datetime',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'ci', 'ci');
    }
}

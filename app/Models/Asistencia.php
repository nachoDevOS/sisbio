<?php

namespace App\Models;

use Database\Factories\AsistenciaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    /** @use HasFactory<AsistenciaFactory> */
    use HasFactory, SoftDeletes;

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

    /**
     * Filtra por CI de la marcación o por nombre del funcionario. Cada palabra
     * del texto debe aparecer en el CI o en algún nombre, así "ignacio molina"
     * cruza nombres + paterno aunque estén en columnas distintas.
     */
    public function scopeBuscar(Builder $query, string $texto): Builder
    {
        foreach (Persona::terminos($texto) as $termino) {
            $query->where(fn (Builder $sub) => $sub
                ->where('ci', 'like', "%{$termino}%")
                ->orWhereHas('persona', fn (Builder $persona) => $persona
                    ->where(fn (Builder $nombre) => $nombre->coincideNombre($termino))));
        }

        return $query;
    }
}

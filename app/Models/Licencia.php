<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Licencia/permiso en la base local (MySQL), migrada desde «Licencias» del SIA.
 *
 * Conexión por defecto (MySQL), con id propio, timestamps y eliminación lógica.
 * El carnet vive en `ci` (en el SIA era IdPersona). `lEntra`/`lSale` guardan la
 * hora sobre la fecha base 1899-12-30, como el SIA real.
 */
class Licencia extends Model
{
    use SoftDeletes;

    protected $table = 'licencias';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fechaPedido',
        'usuario',
        'fecha',
        'ci',
        'idTurno',
        'turno_id',
        'lEntra',
        'lSale',
        'tCompleto',
        'motivo',
        'goceHaberes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fechaPedido' => 'datetime',
            'fecha' => 'datetime',
            'lEntra' => 'datetime',
            'lSale' => 'datetime',
            'tCompleto' => 'boolean',
            'goceHaberes' => 'boolean',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Funcionario en la base local (MySQL), migrado desde «Personas» del SIA.
 *
 * A diferencia del modelo legado App\Models\Sia\Persona (conexión sqlsrv de
 * solo lectura), este usa la conexión por defecto (MySQL), tiene id propio,
 * timestamps y eliminación lógica. El carnet es la columna única `ci`.
 */
class Persona extends Model
{
    use SoftDeletes;

    protected $table = 'personas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ci',
        'origenId',
        'paterno',
        'materno',
        'nombres',
        'fechaNacimiento',
        'lugarNacimiento',
        'sexo',
        'estadoCivil',
        'codigoProfesion',
        'nivelEstudio',
        'telefono',
        'direccion',
        'correo',
        'marcaDirecta',
        'pinReloj',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fechaNacimiento' => 'datetime',
            'marcaDirecta' => 'boolean',
        ];
    }

    public function marcaciones(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'ci', 'ci');
    }

    public function profesion(): BelongsTo
    {
        return $this->belongsTo(Profesion::class, 'codigoProfesion', 'codigoProfesion');
    }
}

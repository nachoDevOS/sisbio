<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catálogo de profesiones en la base local (MySQL), migrado desde «Profesiones»
 * del SIA. Conexión por defecto, con id propio, timestamps y eliminación lógica.
 */
class Profesion extends Model
{
    use SoftDeletes;

    protected $table = 'profesiones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigoProfesion',
        'nombreProfesion',
    ];

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'codigoProfesion', 'codigoProfesion');
    }
}

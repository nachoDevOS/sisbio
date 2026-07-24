<?php

namespace App\Models;

use App\Traits\RegistersUserEvents;
use Database\Factories\ProfesionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catálogo de profesiones en la base local (MySQL), migrado desde «Profesiones»
 * del SIA. Conexión por defecto, con id propio, timestamps y eliminación lógica.
 */
class Profesion extends Model
{
    /** @use HasFactory<ProfesionFactory> */
    use HasFactory, RegistersUserEvents, SoftDeletes;

    protected $table = 'profesiones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'codigoProfesion',
        'nombreProfesion',
        'observacion',
        'estado',
    ];

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'codigoProfesion', 'codigoProfesion');
    }
}

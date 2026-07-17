<?php

namespace App\Models\Sia;

use Database\Factories\Sia\ProfesionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de profesiones del sistema SIA (SQL Server 2008 R2 remoto).
 *
 * Solo se usa para poblar el select de profesión en el alta de funcionarios;
 * el panel no administra este catálogo.
 */
class Profesion extends Model
{
    /** @use HasFactory<ProfesionFactory> */
    use HasFactory;

    protected $connection = 'sia';

    protected $table = 'Profesiones';

    protected $primaryKey = 'CodigoProfesion';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'CodigoProfesion',
        'NombreProfesion',
    ];
}

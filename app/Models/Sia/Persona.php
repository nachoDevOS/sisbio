<?php

namespace App\Models\Sia;

use Database\Factories\Sia\PersonaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Funcionario registrado en el sistema SIA (SQL Server 2008 R2 remoto).
 *
 * Tabla legada de solo lectura: el panel nunca escribe sobre ella. Los campos
 * char() del servidor llegan con relleno de espacios, por eso los accesores
 * aplican trim() antes de mostrar.
 */
class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory;

    protected $connection = 'sia';

    protected $table = 'Personas';

    protected $primaryKey = 'IdPersona';

    protected $keyType = 'string';

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
            'FechaNacimiento' => 'datetime',
            'MarcaDirecta' => 'boolean',
        ];
    }

    public function marcaciones(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'IdPersona', 'IdPersona');
    }

    /**
     * Nombre completo sin el relleno de los char() legados.
     */
    public function getNombreCompletoAttribute(): string
    {
        return collect([$this->Nombres, $this->Paterno, $this->Materno])
            ->map(fn (?string $parte): string => trim((string) $parte))
            ->filter()
            ->implode(' ');
    }
}

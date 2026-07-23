<?php

namespace App\Models\Sia;

use Database\Factories\Sia\PersonaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Funcionario registrado en el sistema SIA (SQL Server 2008 R2 remoto).
 *
 * Tabla legada compartida con el sistema de escritorio del SIA: el panel
 * lista y también da de alta funcionarios sobre ella. Los campos char() del
 * servidor llegan con relleno de espacios, por eso los accesores aplican
 * trim() antes de mostrar.
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
     * Formato ISO 8601 con "T": el único que SQL Server 2008 interpreta
     * igual con cualquier SET LANGUAGE. Con "Y-m-d H:i:s" y el login en
     * español, el servidor lee año-día-mes y revienta con fechas válidas.
     */
    protected $dateFormat = 'Y-m-d\TH:i:s';

    protected $fillable = [
        'IdPersona',
        'OrigenId',
        'Paterno',
        'Materno',
        'Nombres',
        'FechaNacimiento',
        'LugarNacimiento',
        'Sexo',
        'EstadoCivil',
        'CodigoProfesion',
        'NivelEstudio',
        'Telefono',
        'Direccion',
        'CorreoE',
        'PinReloj',
        'MarcaDirecta',
    ];

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
     * Filtra por CI o nombre. Parte el texto en palabras y exige que cada una
     * aparezca en el CI o en alguno de los nombres; así "ignacio molina" cruza
     * Nombres + Paterno aunque estén en columnas distintas. (Antes se buscaba
     * el texto entero contra una sola columna y una búsqueda de dos palabras
     * nunca daba resultados.)
     */
    public function scopeBuscar(Builder $query, string $texto): Builder
    {
        foreach (self::terminos($texto) as $termino) {
            $query->where(fn (Builder $sub) => $sub
                ->where('IdPersona', 'like', "%{$termino}%")
                ->orWhere('Nombres', 'like', "%{$termino}%")
                ->orWhere('Paterno', 'like', "%{$termino}%")
                ->orWhere('Materno', 'like', "%{$termino}%"));
        }

        return $query;
    }

    /**
     * Un término (una palabra) contra las tres columnas de nombre. Pensado para
     * usarse dentro de whereHas('persona') al buscar marcaciones por nombre.
     */
    public function scopeCoincideNombre(Builder $query, string $termino): Builder
    {
        return $query->where('Nombres', 'like', "%{$termino}%")
            ->orWhere('Paterno', 'like', "%{$termino}%")
            ->orWhere('Materno', 'like', "%{$termino}%");
    }

    /**
     * Parte el texto de búsqueda en palabras no vacías.
     *
     * @return list<string>
     */
    public static function terminos(string $texto): array
    {
        return preg_split('/\s+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    public function profesion(): BelongsTo
    {
        return $this->belongsTo(Profesion::class, 'CodigoProfesion', 'CodigoProfesion');
    }

    /**
     * URLs con el carnet sin el relleno del char(12).
     */
    public function getRouteKey(): string
    {
        return trim((string) $this->getAttribute($this->getRouteKeyName()));
    }

    /**
     * Resuelve el binding probando el carnet tal cual y rellenado a 12:
     * SQL Server ignora los espacios finales del char(), pero el sqlite de
     * los tests compara exacto y los registros de fábrica llegan rellenados.
     */
    public function resolveRouteBinding($value, $field = null): ?Persona
    {
        $columna = $field ?? $this->getRouteKeyName();

        return $this->newQuery()
            ->where($columna, (string) $value)
            ->orWhere($columna, str_pad((string) $value, 12))
            ->first();
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

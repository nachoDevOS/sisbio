<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación para dar de alta un funcionario en la tabla Personas
 * del SIA (SQL Server 2008 R2 remoto).
 *
 * Respetan los tamaños y campos NOT NULL de la tabla legada, para guardar
 * datos consistentes con el sistema de escritorio del SIA.
 */
class StorePersonaRequest extends FormRequest
{
    /**
     * Niveles de estudio que ya usa la base del SIA.
     *
     * @var list<string>
     */
    public const NIVELES_ESTUDIO = [
        'Primarios',
        'Secundarios',
        'Bachiller',
        'Técnico Medio',
        'Técnico Superior',
        'Egresado Univ.',
        'Profesional',
        'Diplomado',
        'Masterado',
        'Doctorado',
        'PHD',
    ];

    /**
     * La autorización la resuelve el middleware `auth` de la ruta; aquí solo
     * dejamos pasar la petición ya autenticada.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'IdPersona' => ['required', 'string', 'max:12', Rule::unique('sia.Personas', 'IdPersona')],
            'OrigenId' => ['nullable', 'string', 'max:3'],
            'Paterno' => ['required', 'string', 'max:25'],
            'Materno' => ['nullable', 'string', 'max:25'],
            'Nombres' => ['required', 'string', 'max:35'],
            'FechaNacimiento' => ['required', 'date', 'before_or_equal:today'],
            'LugarNacimiento' => ['nullable', 'string', 'max:25'],
            'Sexo' => ['required', Rule::in(['F', 'M'])],
            'EstadoCivil' => ['required', Rule::in(['S', 'C', 'D', 'V'])],
            'CodigoProfesion' => ['required', Rule::exists('sia.Profesiones', 'CodigoProfesion')],
            'NivelEstudio' => ['nullable', Rule::in(self::NIVELES_ESTUDIO)],
            'Telefono' => ['nullable', 'string', 'max:20'],
            'Direccion' => ['nullable', 'string', 'max:40'],
            'CorreoE' => ['nullable', 'email', 'max:40'],
        ];
    }

    /**
     * El CI llega a veces con espacios (la tabla legada usa char(12)):
     * se normaliza antes de validar y guardar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'IdPersona' => trim((string) $this->input('IdPersona')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'IdPersona' => 'carnet de identidad',
            'OrigenId' => 'expedido en',
            'Paterno' => 'apellido paterno',
            'Materno' => 'apellido materno',
            'Nombres' => 'nombres',
            'FechaNacimiento' => 'fecha de nacimiento',
            'LugarNacimiento' => 'lugar de nacimiento',
            'Sexo' => 'sexo',
            'EstadoCivil' => 'estado civil',
            'CodigoProfesion' => 'profesión',
            'NivelEstudio' => 'nivel de estudios',
            'Telefono' => 'teléfonos',
            'Direccion' => 'dirección',
            'CorreoE' => 'e-mail',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'IdPersona.unique' => 'Ya existe un funcionario registrado con ese carnet.',
        ];
    }
}

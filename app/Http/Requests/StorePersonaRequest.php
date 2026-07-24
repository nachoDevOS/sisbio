<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación para dar de alta un funcionario en la tabla local
 * `personas` (MySQL). Respetan los tamaños y campos NOT NULL de la tabla.
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
     * La autorización la resuelve la policy en el controlador.
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
            'ci' => ['required', 'string', 'max:12', Rule::unique('personas', 'ci')],
            'origenId' => ['nullable', 'string', 'max:3'],
            'paterno' => ['required', 'string', 'max:25'],
            'materno' => ['nullable', 'string', 'max:25'],
            'nombres' => ['required', 'string', 'max:35'],
            'fechaNacimiento' => ['required', 'date', 'before_or_equal:today'],
            'lugarNacimiento' => ['nullable', 'string', 'max:25'],
            'sexo' => ['required', Rule::in(['F', 'M'])],
            'estadoCivil' => ['required', Rule::in(['S', 'C', 'D', 'V'])],
            'codigoProfesion' => ['required', Rule::exists('profesiones', 'codigoProfesion')],
            'nivelEstudio' => ['nullable', Rule::in(self::NIVELES_ESTUDIO)],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:40'],
            'correo' => ['nullable', 'email', 'max:40'],
        ];
    }

    /**
     * El CI llega a veces con espacios: se normaliza antes de validar y guardar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ci' => trim((string) $this->input('ci')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ci' => 'carnet de identidad',
            'origenId' => 'expedido en',
            'paterno' => 'apellido paterno',
            'materno' => 'apellido materno',
            'nombres' => 'nombres',
            'fechaNacimiento' => 'fecha de nacimiento',
            'lugarNacimiento' => 'lugar de nacimiento',
            'sexo' => 'sexo',
            'estadoCivil' => 'estado civil',
            'codigoProfesion' => 'profesión',
            'nivelEstudio' => 'nivel de estudios',
            'telefono' => 'teléfonos',
            'direccion' => 'dirección',
            'correo' => 'e-mail',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ci.unique' => 'Ya existe un funcionario registrado con ese carnet.',
        ];
    }
}

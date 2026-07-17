<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación para editar un funcionario de la tabla Personas del SIA.
 *
 * El carnet (IdPersona) es la clave primaria de la tabla legada y no se
 * edita: el formulario lo muestra deshabilitado y aquí no se valida ni se
 * guarda. La sección "Control de asistencia" (PinReloj, MarcaDirecta) también
 * queda fuera mientras siga gestionándose desde el sistema de escritorio.
 */
class UpdatePersonaRequest extends FormRequest
{
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
            'OrigenId' => ['nullable', 'string', 'max:3'],
            'Paterno' => ['required', 'string', 'max:25'],
            'Materno' => ['nullable', 'string', 'max:25'],
            'Nombres' => ['required', 'string', 'max:35'],
            'FechaNacimiento' => ['required', 'date', 'before_or_equal:today'],
            'LugarNacimiento' => ['nullable', 'string', 'max:25'],
            'Sexo' => ['required', Rule::in(['F', 'M'])],
            'EstadoCivil' => ['required', Rule::in(['S', 'C', 'D', 'V'])],
            'CodigoProfesion' => ['required', Rule::exists('sia.Profesiones', 'CodigoProfesion')],
            'NivelEstudio' => ['nullable', Rule::in(StorePersonaRequest::NIVELES_ESTUDIO)],
            'Telefono' => ['nullable', 'string', 'max:20'],
            'Direccion' => ['nullable', 'string', 'max:40'],
            'CorreoE' => ['nullable', 'email', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return (new StorePersonaRequest)->attributes();
    }
}

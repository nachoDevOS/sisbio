<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación para editar un funcionario de la tabla local `personas`.
 *
 * El carnet (ci) es la clave de negocio y no se edita: el formulario lo muestra
 * deshabilitado y aquí no se valida ni se guarda. La sección "Control de
 * asistencia" (pinReloj, marcaDirecta) también queda fuera por ahora.
 */
class UpdatePersonaRequest extends FormRequest
{
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
            'origenId' => ['nullable', 'string', 'max:3'],
            'paterno' => ['required', 'string', 'max:25'],
            'materno' => ['nullable', 'string', 'max:25'],
            'nombres' => ['required', 'string', 'max:35'],
            'fechaNacimiento' => ['required', 'date', 'before_or_equal:today'],
            'lugarNacimiento' => ['nullable', 'string', 'max:25'],
            'sexo' => ['required', Rule::in(['F', 'M'])],
            'estadoCivil' => ['required', Rule::in(['S', 'C', 'D', 'V'])],
            'codigoProfesion' => ['required', Rule::exists('profesiones', 'codigoProfesion')],
            'nivelEstudio' => ['nullable', Rule::in(StorePersonaRequest::NIVELES_ESTUDIO)],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:40'],
            'correo' => ['nullable', 'email', 'max:40'],
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

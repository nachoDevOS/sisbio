<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación para dar de alta un equipo biométrico, consistentes
 * con la estructura de la tabla `equipos`.
 */
class StoreEquipoRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255'],
            'ip' => [
                'required',
                'ip',
                // Un mismo equipo (ip + puerto) no puede registrarse dos veces.
                Rule::unique('equipos')->where(fn ($query) => $query->where('puerto', $this->input('puerto'))),
            ],
            'puerto' => ['required', 'integer', 'min:1', 'max:65535'],
            'comm_key' => ['required', 'integer', 'min:0'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'es_master' => ['boolean'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * Los checkboxes ausentes en el formulario no llegan en la petición: los
     * normalizamos a booleanos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'es_master' => $this->boolean('es_master'),
            'activo' => $this->boolean('activo'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ip.unique' => 'Ya existe un equipo registrado con esa IP y puerto.',
            'ip.ip' => 'La dirección IP no es válida.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reglas de validación para el CSV de marcaciones a importar en la tabla
 * Asistencia del SIA. La autorización real ('Create:Asistencia') la hace
 * MarcacionController vía policy.
 */
class ImportarMarcacionesRequest extends FormRequest
{
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
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'archivo' => 'archivo CSV',
        ];
    }
}

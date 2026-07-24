<?php

namespace App\Http\Requests;

use App\Models\Asistencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de una marcación manual (tipo M) creada desde la pantalla de
 * Marcaciones. El CI debe pertenecer a un funcionario local.
 */
class StoreMarcacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Asistencia::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', Rule::exists('personas', 'ci')],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i,H:i:s'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ci.exists' => 'No existe un funcionario con ese CI.',
        ];
    }
}

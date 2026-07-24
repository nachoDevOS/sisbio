<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación para editar un día excepcional. La unicidad de `fecha`
 * ignora la propia fila.
 */
class UpdateDiaExcepcionalRequest extends FormRequest
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
            'fecha' => [
                'required',
                'date',
                Rule::unique('dias_excepcionales', 'fecha')->ignore($this->route('diaExcepcional')),
            ],
            'motivoInasistencia' => ['required', 'string', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * La fecha se guarda al inicio del día para que el índice único trabaje por
     * día (no por instante) y coincida entre validación y almacenamiento.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('fecha')) {
            $this->merge([
                'fecha' => Carbon::parse($this->input('fecha'))->startOfDay()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha.unique' => 'Ya existe un día excepcional registrado para esa fecha.',
        ];
    }
}

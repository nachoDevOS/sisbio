<?php

namespace App\Http\Requests;

use App\Models\AsignacionTurno;
use App\Models\Turno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas para asignarle un turno a un funcionario. El turno se elige por su id
 * (`turno_id`, la FK real); el código histórico `idTurno` lo copia el
 * controlador desde el turno elegido y nunca llega del formulario.
 */
class StoreAsignacionTurnoRequest extends FormRequest
{
    /**
     * Se autoriza acá y no solo en el controlador: la validación corre antes,
     * así que un usuario sin permiso recibiría errores de campos en vez del 403.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', AsignacionTurno::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', 'max:12'],
            'turno_id' => [
                'required',
                'integer',
                Rule::exists('turnos', 'id')->whereNull('deleted_at'),
            ],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * La tabla tiene una clave única (ci + idTurno + desde) que **incluye las
     * filas borradas lógicamente**, así que se comprueba con `withTrashed()`:
     * si no, el alta reventaría contra la base en vez de avisar.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $codigo = Turno::query()->whereKey($this->integer('turno_id'))->value('idTurno');

                $repetida = AsignacionTurno::withTrashed()
                    ->where('ci', trim((string) $this->input('ci')))
                    ->where('idTurno', $codigo)
                    ->whereDate('desde', $this->date('desde'))
                    ->exists();

                if ($repetida) {
                    $validator->errors()->add(
                        'turno_id',
                        'Ese funcionario ya tiene asignado ese turno desde esa fecha.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ci' => 'funcionario',
            'turno_id' => 'turno',
            'desde' => 'fecha desde',
            'hasta' => 'fecha hasta',
            'observacion' => 'observación',
        ];
    }
}

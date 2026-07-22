<?php

namespace App\Http\Requests;

use App\Policies\RolePolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación para crear un rol: nombre único y una lista de
 * permisos, cada uno validado contra RolePolicy::nombresDePermiso().
 */
class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string', Rule::in(RolePolicy::nombresDePermiso())],
        ];
    }
}

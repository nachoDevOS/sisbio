<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Policies\RolePolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string', Rule::in(RolePolicy::nombresDePermiso())],
        ];
    }
}

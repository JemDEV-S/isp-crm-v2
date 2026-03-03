<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('accesscontrol.role.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:roles,code', 'regex:/^[a-z0-9_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'is_active' => 'estado activo',
            'permissions' => 'permisos',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo puede contener letras minúsculas, números y guiones bajos.',
        ];
    }
}

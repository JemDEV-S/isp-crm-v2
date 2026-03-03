<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('accesscontrol.user.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'is_active' => ['boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'phone' => 'teléfono',
            'zone_id' => 'zona',
            'is_active' => 'estado activo',
            'roles' => 'roles',
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Debe asignar al menos un rol al usuario.',
            'roles.min' => 'Debe asignar al menos un rol al usuario.',
        ];
    }
}

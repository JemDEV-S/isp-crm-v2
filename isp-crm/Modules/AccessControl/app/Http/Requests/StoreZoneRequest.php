<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('accesscontrol.zone.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:zones,code', 'regex:/^[A-Z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:zones,id'],
            'polygon' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'parent_id' => 'zona padre',
            'polygon' => 'polígono',
            'is_active' => 'estado activo',
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'El código solo puede contener letras mayúsculas, números, guiones y guiones bajos.',
        ];
    }
}

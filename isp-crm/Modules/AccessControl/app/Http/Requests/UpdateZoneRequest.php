<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('accesscontrol.zone.update');
    }

    public function rules(): array
    {
        $zoneId = $this->route('zone')->id ?? $this->route('zone');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:zones,id',
                function ($attribute, $value, $fail) use ($zoneId) {
                    if ($value == $zoneId) {
                        $fail('Una zona no puede ser su propia zona padre.');
                    }
                },
            ],
            'polygon' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'parent_id' => 'zona padre',
            'polygon' => 'polígono',
            'is_active' => 'estado activo',
        ];
    }
}

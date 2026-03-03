<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('network.node.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:nodes,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:tower,datacenter,pop,cabinet'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,inactive,maintenance'],
            'description' => ['nullable', 'string', 'max:500'],
            'commissioned_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código del nodo es obligatorio',
            'code.unique' => 'Ya existe un nodo con este código',
            'name.required' => 'El nombre del nodo es obligatorio',
            'type.required' => 'El tipo de nodo es obligatorio',
            'type.in' => 'El tipo de nodo debe ser: tower, datacenter, pop o cabinet',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.between' => 'La longitud debe estar entre -180 y 180',
        ];
    }
}

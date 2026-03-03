<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNapBoxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('network.napbox.create');
    }

    public function rules(): array
    {
        return [
            'node_id' => ['required', 'integer', 'exists:nodes,id'],
            'code' => ['required', 'string', 'max:50', 'unique:nap_boxes,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:splitter_1x4,splitter_1x8,splitter_1x16,splitter_1x32'],
            'total_ports' => ['required', 'integer', 'in:4,8,16,32'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive,maintenance'],
            'installed_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'node_id.required' => 'El nodo es obligatorio',
            'node_id.exists' => 'El nodo seleccionado no existe',
            'code.required' => 'El código de la NAP es obligatorio',
            'code.unique' => 'Ya existe una NAP con este código',
            'name.required' => 'El nombre es obligatorio',
            'type.required' => 'El tipo de splitter es obligatorio',
            'total_ports.required' => 'La cantidad de puertos es obligatoria',
            'total_ports.in' => 'La cantidad de puertos debe ser 4, 8, 16 o 32',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $ports = $this->input('total_ports');

            $expectedPorts = [
                'splitter_1x4' => 4,
                'splitter_1x8' => 8,
                'splitter_1x16' => 16,
                'splitter_1x32' => 32,
            ];

            if (isset($expectedPorts[$type]) && $expectedPorts[$type] != $ports) {
                $validator->errors()->add('total_ports', "Para un splitter {$type} se esperan {$expectedPorts[$type]} puertos");
            }
        });
    }
}

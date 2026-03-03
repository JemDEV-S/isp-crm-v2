<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Network\Enums\DeviceType;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('network.device.create');
    }

    public function rules(): array
    {
        $deviceTypes = array_column(DeviceType::cases(), 'value');

        return [
            'node_id' => ['required', 'integer', 'exists:nodes,id'],
            'type' => ['required', 'string', 'in:' . implode(',', $deviceTypes)],
            'brand' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', 'unique:devices,serial_number'],
            'ip_address' => ['nullable', 'ip'],
            'mac_address' => ['nullable', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
            'snmp_community' => ['nullable', 'string', 'max:100'],
            'api_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'api_user' => ['nullable', 'string', 'max:100'],
            'api_password' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive,maintenance,decommissioned'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'node_id.required' => 'El nodo es obligatorio',
            'node_id.exists' => 'El nodo seleccionado no existe',
            'type.required' => 'El tipo de dispositivo es obligatorio',
            'brand.required' => 'La marca es obligatoria',
            'model.required' => 'El modelo es obligatorio',
            'serial_number.unique' => 'Ya existe un dispositivo con este número de serie',
            'ip_address.ip' => 'La dirección IP no es válida',
            'mac_address.regex' => 'El formato de MAC debe ser XX:XX:XX:XX:XX:XX',
            'api_port.min' => 'El puerto API debe ser mayor a 0',
            'api_port.max' => 'El puerto API debe ser menor a 65536',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            if (in_array($type, ['router', 'olt'])) {
                if (empty($this->input('ip_address'))) {
                    $validator->errors()->add('ip_address', 'La IP es requerida para dispositivos tipo ' . $type);
                }
            }
        });
    }
}

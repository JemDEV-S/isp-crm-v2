<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Network\Enums\DeviceType;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('network.device.update');
    }

    public function rules(): array
    {
        $deviceTypes = array_column(DeviceType::cases(), 'value');

        return [
            'node_id' => ['sometimes', 'integer', 'exists:nodes,id'],
            'type' => ['sometimes', 'string', 'in:' . implode(',', $deviceTypes)],
            'brand' => ['sometimes', 'string', 'max:50'],
            'model' => ['sometimes', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('devices', 'serial_number')->ignore($this->route('device'))],
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
}

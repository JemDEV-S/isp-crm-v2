<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIpPoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('network.ippool.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'network_cidr' => ['required', 'string', 'regex:/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/'],
            'gateway' => ['required', 'ip'],
            'dns_primary' => ['nullable', 'ip'],
            'dns_secondary' => ['nullable', 'ip'],
            'type' => ['required', 'string', 'in:public,private,cgnat'],
            'vlan_id' => ['nullable', 'integer', 'min:1', 'max:4094'],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'is_active' => ['nullable', 'boolean'],
            'populate_addresses' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del pool es obligatorio',
            'network_cidr.required' => 'La red CIDR es obligatoria',
            'network_cidr.regex' => 'El formato debe ser X.X.X.X/XX (ej: 192.168.1.0/24)',
            'gateway.required' => 'El gateway es obligatorio',
            'gateway.ip' => 'El gateway debe ser una IP válida',
            'type.required' => 'El tipo de pool es obligatorio',
            'type.in' => 'El tipo debe ser: public, private o cgnat',
            'vlan_id.min' => 'El VLAN ID debe ser mayor a 0',
            'vlan_id.max' => 'El VLAN ID debe ser menor a 4095',
            'device_id.exists' => 'El dispositivo seleccionado no existe',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $cidr = $this->input('network_cidr');
            $gateway = $this->input('gateway');

            if ($cidr && $gateway) {
                if (!$this->isIpInCidr($gateway, $cidr)) {
                    $validator->errors()->add('gateway', 'El gateway debe pertenecer a la red especificada');
                }
            }
        });
    }

    private function isIpInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}

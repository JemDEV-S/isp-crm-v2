<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignIpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('network.ip.assign');
    }

    public function rules(): array
    {
        return [
            'pool_id' => ['required', 'integer', 'exists:ip_pools,id'],
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pool_id.required' => 'El pool de IP es obligatorio',
            'pool_id.exists' => 'El pool seleccionado no existe',
            'subscription_id.required' => 'La suscripción es obligatoria',
            'subscription_id.exists' => 'La suscripción seleccionada no existe',
        ];
    }
}
